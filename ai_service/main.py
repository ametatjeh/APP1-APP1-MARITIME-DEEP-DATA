import sys
import os
import time
from typing import List, Dict, Any, Optional
from fastapi import FastAPI, HTTPException, Header, Depends
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field

from ai_service.agents.orchestrator import OrchestratorAgent
from ai_service.rag.vector_store import generate_embedding, cosine_similarity, chunk_text

app = FastAPI(
    title="Maritime Deep Data AI Microservice",
    description="Multi-Agent investigative intelligence, vector embedding, and RAG synthesis engine.",
    version="1.0.0"
)

# Production-configurable CORS allowlist (Internal network / Laravel API origin)
allowed_origins_env = os.environ.get("AI_CORS_ORIGINS", "*")
allowed_origins = [o.strip() for o in allowed_origins_env.split(",") if o.strip()]

app.add_middleware(
    CORSMiddleware,
    allow_origins=allowed_origins if allowed_origins else ["*"],
    allow_credentials=True,
    allow_methods=["GET", "POST", "OPTIONS"],
    allow_headers=["*"],
)

orchestrator = OrchestratorAgent()

def verify_internal_auth(x_internal_token: Optional[str] = Header(None, alias="X-Internal-Token")):
    """
    Verify internal shared token between Laravel Backend and FastAPI Microservice.
    If AI_SERVICE_TOKEN is not configured (e.g., local tests), authentication passes.
    In production, a mismatch immediately raises 401 Unauthorized.
    """
    expected_token = os.environ.get("AI_SERVICE_TOKEN")
    if expected_token:
        if not x_internal_token or x_internal_token != expected_token:
            raise HTTPException(status_code=401, detail="Unauthorized: invalid or missing internal service token")
    return True

class OrchestrateRequest(BaseModel):
    prompt: str
    context: Dict[str, Any] = Field(default_factory=dict)
    agent_mode: str = "orchestrator"

class EmbedRequest(BaseModel):
    text: str
    dimension: int = 64

class VectorSearchItem(BaseModel):
    id: str
    title: str
    content: str
    embedding: List[float]
    metadata: Dict[str, Any] = Field(default_factory=dict)

class VectorSearchRequest(BaseModel):
    query: str
    items: List[VectorSearchItem]
    top_k: int = 5

@app.get("/health")
def health_check():
    """
    Service health check endpoint for monitoring, load balancers, and supervisor.
    Does not expose sensitive credentials.
    """
    return {
        "status": "healthy",
        "service": "Maritime Deep Data AI Service",
        "framework": "FastAPI",
        "python_version": sys.version.split()[0],
        "multi_agent_enabled": True,
        "token_protection_enabled": bool(os.environ.get("AI_SERVICE_TOKEN")),
        "timestamp": int(time.time()),
    }

@app.get("/api/v1/agents", dependencies=[Depends(verify_internal_auth)])
def list_agents():
    return {
        "orchestrator": "OrchestratorAgent",
        "specialist_agents": [
            {"name": "VesselIntelligenceAgent", "domain": "Vessel master, flag-hopping, identity verification"},
            {"name": "IdentityResolutionAgent", "domain": "Identifier collisions, duplicate detection, alias analysis"},
            {"name": "CorporateIntelligenceAgent", "domain": "Beneficial ownership, offshore secrecy, corporate networks"},
            {"name": "CrewResearchAgent", "domain": "Crew welfare, forced labour (ILO 11), manning agencies"},
            {"name": "MaritimeJurisdictionAgent", "domain": "EEZ baselines, overlapping claims, Natuna waters"},
            {"name": "IuuResearchAgent", "domain": "Official RFMO blacklists, unlicensed operations, sanctions"},
            {"name": "EvidenceAnalysisAgent", "domain": "Chain of custody, SHA-256 verification, source provenance"},
            {"name": "ResearchAssistantAgent", "domain": "Evidence-grounded reporting, recommendations for investigators"}
        ]
    }

@app.post("/api/v1/orchestrate", dependencies=[Depends(verify_internal_auth)])
def orchestrate_query(req: OrchestrateRequest):
    try:
        result = orchestrator.process_query(
            prompt=req.prompt,
            context=req.context,
            agent_mode=req.agent_mode
        )
        return result
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/api/v1/embed", dependencies=[Depends(verify_internal_auth)])
def embed_text(req: EmbedRequest):
    embedding = generate_embedding(req.text, dimension=req.dimension)
    return {
        "text": req.text,
        "dimension": req.dimension,
        "embedding": embedding
    }

@app.post("/api/v1/vector-search", dependencies=[Depends(verify_internal_auth)])
def vector_search(req: VectorSearchRequest):
    query_vector = generate_embedding(req.query, dimension=64)
    scored_items = []

    for item in req.items:
        sim = cosine_similarity(query_vector, item.embedding)
        scored_items.append({
            "id": item.id,
            "title": item.title,
            "content": item.content,
            "similarity": round(float(sim), 4),
            "metadata": item.metadata
        })

    scored_items.sort(key=lambda x: x["similarity"], reverse=True)
    return {
        "query": req.query,
        "top_k": req.top_k,
        "results": scored_items[:req.top_k]
    }
