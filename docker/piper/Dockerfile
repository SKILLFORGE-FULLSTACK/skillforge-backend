# Serveur TTS français auto-hébergé et 100% gratuit (Piper — MIT), utilisé en
# alternative à Groq TTS qui ne propose pas de voix française actuellement.
# Voix "siwis" (medium) : bon compromis qualité/latence sur un CPU de VM.
FROM python:3.11-slim

RUN apt-get update \
    && apt-get install -y --no-install-recommends curl \
    && rm -rf /var/lib/apt/lists/* \
    && pip install --no-cache-dir "piper-tts[http]"

WORKDIR /app

RUN curl -fL -o fr_FR-siwis-medium.onnx \
    https://huggingface.co/rhasspy/piper-voices/resolve/main/fr/fr_FR/siwis/medium/fr_FR-siwis-medium.onnx \
    && curl -fL -o fr_FR-siwis-medium.onnx.json \
    https://huggingface.co/rhasspy/piper-voices/resolve/main/fr/fr_FR/siwis/medium/fr_FR-siwis-medium.onnx.json

EXPOSE 5000
CMD ["python", "-m", "piper.http_server", "-m", "fr_FR-siwis-medium.onnx", "--host", "0.0.0.0", "--port", "5000"]
