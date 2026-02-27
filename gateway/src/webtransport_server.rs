use anyhow::Result;
use std::time::Duration;
use tracing::info;
use tracing::info_span;
use tracing::Instrument;
use wtransport::endpoint::endpoint_side::Server;
use wtransport::endpoint::IncomingSession;
use wtransport::Endpoint;
use wtransport::Identity;
use wtransport::ServerConfig;

pub struct WebTransportServer {
    endpoint: Endpoint<Server>,
}

impl WebTransportServer {
    pub fn new(identity: Identity, port: u16) -> Result<Self> {
        let config = ServerConfig::builder()
            .with_bind_default(port)
            .with_identity(identity)
            .keep_alive_interval(Some(Duration::from_secs(3)))
            .build();

        let endpoint = Endpoint::server(config)?;
        Ok(Self { endpoint })
    }

    pub fn local_port(&self) -> u16 {
        self.endpoint.local_addr().unwrap().port()
    }

    pub async fn serve(self) -> Result<()> {
        info!("WebTransport listening on UDP port {}", self.local_port());

        for id in 0.. {
            let incoming_session = self.endpoint.accept().await;
            tokio::spawn(
                Self::handle_incoming_session(incoming_session).instrument(info_span!("wt", id)),
            );
        }

        Ok(())
    }

    async fn handle_incoming_session(incoming_session: IncomingSession) {
        async fn impl_(incoming_session: IncomingSession) -> Result<()> {
            let mut buffer = vec![0; 65536].into_boxed_slice();

            info!("Waiting for session request...");
            let session_request = incoming_session.await?;

            info!(
                "New session: authority='{}' path='{}'",
                session_request.authority(),
                session_request.path()
            );

            let connection = session_request.accept().await?;
            info!("Session ready; waiting for client data...");

            loop {
                tokio::select! {
                    stream = connection.accept_bi() => {
                        let mut stream = stream?;
                        info!("Accepted BI stream");

                        let Some(bytes_read) = stream.1.read(&mut buffer).await? else {
                            continue;
                        };

                        let str_data = std::str::from_utf8(&buffer[..bytes_read])?;
                        info!("Received (bi) '{str_data}'");

                        stream.0.write_all(b"ACK").await?;
                    }
                    stream = connection.accept_uni() => {
                        let mut stream = stream?;
                        info!("Accepted UNI stream");

                        let Some(bytes_read) = stream.read(&mut buffer).await? else {
                            continue;
                        };

                        let str_data = std::str::from_utf8(&buffer[..bytes_read])?;
                        info!("Received (uni) '{str_data}'");

                        let mut stream = connection.open_uni().await?.await?;
                        stream.write_all(b"ACK").await?;
                    }
                    dgram = connection.receive_datagram() => {
                        let dgram = dgram?;
                        let str_data = std::str::from_utf8(&dgram)?;
                        info!("Received (dgram) '{str_data}'");

                        connection.send_datagram(b"ACK")?;
                    }
                }
            }
        }

        let result = impl_(incoming_session).await;
        info!("Session ended: {:?}", result);
    }
}

