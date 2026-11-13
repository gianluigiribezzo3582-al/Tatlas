from fastapi import FastAPI

from app.config import get_settings

app = FastAPI(title="Tatlas AI service", version="0.1.0")


@app.get("/health")
def health() -> dict:
    settings = get_settings()
    return {
        "status": "ok",
        "models": {
            "text": settings.text_model,
            "image": settings.image_model,
            "loaded": settings.load_models,
        },
    }
