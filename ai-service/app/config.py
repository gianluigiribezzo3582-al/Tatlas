from functools import lru_cache

from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    """Runtime configuration, read from environment variables (prefix AI_)."""

    model_config = SettingsConfigDict(env_prefix="AI_")

    text_model: str = "intfloat/multilingual-e5-small"
    image_model: str = "google/siglip2-base-patch16-224"
    # Phase 1 does not load any model; Phase 2 flips this on.
    load_models: bool = False


@lru_cache
def get_settings() -> Settings:
    return Settings()
