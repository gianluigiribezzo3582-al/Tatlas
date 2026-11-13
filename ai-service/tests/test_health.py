from fastapi.testclient import TestClient

from app.main import app

client = TestClient(app)


def test_health_reports_ok_and_configured_models():
    response = client.get("/health")

    assert response.status_code == 200
    body = response.json()
    assert body["status"] == "ok"
    assert body["models"]["text"] == "intfloat/multilingual-e5-small"
    assert body["models"]["image"] == "google/siglip2-base-patch16-224"
    assert body["models"]["loaded"] is False
