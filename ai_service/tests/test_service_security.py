import os
import pytest
from fastapi.testclient import TestClient
from ai_service.main import app

client = TestClient(app)

def test_health_check_always_accessible():
    response = client.get("/health")
    assert response.status_code == 200
    data = response.json()
    assert data["status"] == "healthy"
    assert data["service"] == "Maritime Deep Data AI Service"
    assert "timestamp" in data

def test_service_auth_enforcement(monkeypatch):
    # Set internal token requirement
    monkeypatch.setenv("AI_SERVICE_TOKEN", "secret-internal-key-9988")

    # Request without token should be 401
    res_no_token = client.post("/api/v1/embed", json={"text": "FV Sea Dragon", "dimension": 64})
    assert res_no_token.status_code == 401
    assert "Unauthorized" in res_no_token.json()["detail"]

    # Request with invalid token should be 401
    res_bad_token = client.post(
        "/api/v1/embed",
        json={"text": "FV Sea Dragon", "dimension": 64},
        headers={"X-Internal-Token": "wrong-token"}
    )
    assert res_bad_token.status_code == 401

    # Request with valid token should succeed
    res_valid = client.post(
        "/api/v1/embed",
        json={"text": "FV Sea Dragon", "dimension": 64},
        headers={"X-Internal-Token": "secret-internal-key-9988"}
    )
    assert res_valid.status_code == 200
    data = res_valid.json()
    assert "embedding" in data
    assert len(data["embedding"]) == 64
