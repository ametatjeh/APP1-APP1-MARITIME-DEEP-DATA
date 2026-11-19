import sys
import os
from typing import List, Dict, Any, Optional
from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field

from ai_service.agents.orchestrator import OrchestratorAgent
from ai_service.rag.vector_store import generate_embedding, cosine_similarity, chunk_text

app = FastAPI(
    title="Maritime Deep Data AI Microservice",
    description="Multi-Agent investigative intelligence, vector embedding, and RAG synthesis engine.",
    version="1.0.0"
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

orchestrator = OrchestratorAgent()

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
    return {
        "status": "healthy",
        "service": "Maritime Deep Data AI Service",
        "framework": "FastAPI",
        "python_version": sys.version.split()[0],
        "multi_agent_enabled": True
    }

@app.get("/api/v1/agents")
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

@app.post("/api/v1/orchestrate")
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

@app.post("/api/v1/embed")
def embed_text(req: EmbedRequest):
    embedding = generate_embedding(req.text, dimension=req.dimension)
    return {
        "text": req.text,
        "dimension": req.dimension,
        "embedding": embedding
    }

@app.post("/api/v1/vector-search")
def vector_search(req: VectorSearchRequest):
    query_vector = generate_embedding(req.query, dimension=64)
    scored_items = []

    for item in req.items:
        score = cosine_similarity(query_vector, item.embedding)
        scored_items.append({
            "id": item.id,
            "title": item.title,
            "content": item.content,
            "score": score,
            "metadata": item.metadata
        })

    # Sort descending by cosine similarity score
    scored_items.sort(key=lambda x: x["score"], reverse=True)
    return {
        "query": req.query,
        "results": scored_items[:req.top_k]
    }

if __name__ == "__main__":
    import uvicorn
    uvicorn.run("ai_service.main:app", host="127.0.0.1", port=8100, reload=False)
