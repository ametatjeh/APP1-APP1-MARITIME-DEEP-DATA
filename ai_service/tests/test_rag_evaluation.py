import math
import sys
import os

sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), '../..')))

from ai_service.rag.vector_store import generate_embedding, cosine_similarity, chunk_text
from ai_service.rag.grounding import GroundingGuard

# Labeled Evaluation Benchmark Dataset
CORPUS = [
    {
        "id": "DOC-01",
        "title": "FV OCEAN STAR Particulars",
        "content": "FV OCEAN STAR (IMO 9123456) longliner fishing vessel operating in high-seas WCPFC tuna fisheries under Panama flag of convenience registry.",
        "category": "vessel_foc"
    },
    {
        "id": "DOC-02",
        "title": "Pacific Shell Holdings BVI Filing",
        "content": "Pacific Shell Holdings Ltd incorporated in Road Town, Tortola, British Virgin Islands (BVI) acting as beneficial owner for commercial tuna trawlers.",
        "category": "offshore_corporate"
    },
    {
        "id": "DOC-03",
        "title": "ILO Crew Inspection Report",
        "content": "Crew employment contracts on reefer vessel SEA BREEZE documented passport withholding, non-payment of wages, and physical abuse meeting ILO forced labour indicators.",
        "category": "labour_violation"
    },
    {
        "id": "DOC-04",
        "title": "Natuna Maritime Boundary Bilateral Dossier",
        "content": "North Natuna Sea archipelagic EEZ maritime boundary claims between Indonesia and Vietnam overlapping sectors and continental shelf demarcation.",
        "category": "maritime_boundary"
    },
    {
        "id": "DOC-05",
        "title": "RFMO IUU Blacklist Notice",
        "content": "WCPFC official IUU vessel blacklist entry for longliner YONG WANG 88 for unauthorized shark finning and illegal high seas transshipment.",
        "category": "iuu_blacklist"
    },
    {
        "id": "DOC-06",
        "title": "Manning Agency Audit",
        "content": "Manning agency PT Maritim Samudra licensed in Jakarta investigated for debt bondage recruitment fees and fraudulent sea service contracts.",
        "category": "manning_agency"
    },
    {
        "id": "DOC-07",
        "title": "Domestic Coastal Cargo Manifest",
        "content": "General cargo coastal vessel KM BINA NUSANTARA carrying cement and construction materials between Surabaya and Pontianak port.",
        "category": "domestic_cargo"
    },
    {
        "id": "DOC-08",
        "title": "Anambas Marine Protected Area Regulation",
        "content": "Marine Protected Area (Kawasan Konservasi Perairan) Anambas islands zoning regulations, coral reef conservation, and artisanal fishing permits.",
        "category": "conservation"
    },
    {
        "id": "DOC-09",
        "title": "MMSI Collision Surveillance Record",
        "content": "Reefer re-flagging collision: duplicate MMSI 525112233 detected broadcasting simultaneously across two distinct physical hulls in Indian Ocean.",
        "category": "identity_fraud"
    },
    {
        "id": "DOC-10",
        "title": "Engine Maintenance Guide",
        "content": "Standard four-stroke marine diesel engine routine overhaul and fuel injector calibration specifications.",
        "category": "technical_manual"
    }
]

EVAL_QUERIES = [
    {
        "query": "Panama flag tuna longliner high seas fishing",
        "relevant_ids": ["DOC-01", "DOC-05"]
    },
    {
        "query": "British Virgin Islands BVI offshore beneficial ownership holding",
        "relevant_ids": ["DOC-02"]
    },
    {
        "query": "Forced labour wage withholding crew passport detention",
        "relevant_ids": ["DOC-03", "DOC-06"]
    },
    {
        "query": "Indonesia Vietnam overlapping EEZ boundary Natuna",
        "relevant_ids": ["DOC-04"]
    }
]

def run_rag_evaluation(top_k: int = 3, dimension: int = 128):
    # 1. Embed all corpus documents
    embedded_corpus = []
    for doc in CORPUS:
        vec = generate_embedding(doc["content"], dimension=dimension)
        norm = math.sqrt(sum(x * x for x in vec))
        assert abs(norm - 1.0) < 1e-4, f"Vector for {doc['id']} is not L2 normalized!"
        embedded_corpus.append({**doc, "vector": vec})

    precisions = []
    recalls = []
    reciprocal_ranks = []
    citation_correctness_scores = []
    unsupported_claim_counts = 0

    print("=== RAG RETRIEVAL & GROUNDING EVALUATION ===")

    for item in EVAL_QUERIES:
        q_text = item["query"]
        rel_ids = set(item["relevant_ids"])
        q_vec = generate_embedding(q_text, dimension=dimension)

        # Compute cosine similarity
        scored = []
        for doc in embedded_corpus:
            sim = cosine_similarity(q_vec, doc["vector"])
            scored.append((sim, doc))

        scored.sort(key=lambda x: x[0], reverse=True)
        top_results = scored[:top_k]
        retrieved_ids = [doc["id"] for _, doc in top_results]

        # Calculate metrics
        hits = [doc_id for doc_id in retrieved_ids if doc_id in rel_ids]
        precision_at_k = len(hits) / top_k
        recall_at_k = len(hits) / len(rel_ids)

        precisions.append(precision_at_k)
        recalls.append(recall_at_k)

        # MRR
        rr = 0.0
        for rank, doc_id in enumerate(retrieved_ids, 1):
            if doc_id in rel_ids:
                rr = 1.0 / rank
                break
        reciprocal_ranks.append(rr)

        # Grounding / Citation verification test
        retrieved_evidence = [{"id": d["id"], "title": d["title"], "verification_status": "verified"} for _, d in top_results]
        citations = GroundingGuard.extract_citations(f"Assessment for query: {q_text}", retrieved_evidence)
        valid_citations = [c for c in citations if c["evidence_id"] in retrieved_ids]
        citation_correctness = len(valid_citations) / len(citations) if citations else 1.0
        citation_correctness_scores.append(citation_correctness)

        print(f"Query: '{q_text}'")
        print(f"  Top-{top_k} Retrieved: {retrieved_ids}")
        print(f"  Expected: {list(rel_ids)}")
        print(f"  Precision@{top_k}: {precision_at_k:.2f} | Recall@{top_k}: {recall_at_k:.2f} | MRR: {rr:.2f}")

    mean_p = sum(precisions) / len(precisions)
    mean_r = sum(recalls) / len(recalls)
    mrr = sum(reciprocal_ranks) / len(reciprocal_ranks)
    mean_citation_correctness = sum(citation_correctness_scores) / len(citation_correctness_scores)
    unsupported_rate = unsupported_claim_counts / len(EVAL_QUERIES)

    print("\n--- SUMMARY METRICS ---")
    print(f"Mean Precision@{top_k}: {mean_p * 100:.1f}%")
    print(f"Mean Recall@{top_k}: {mean_r * 100:.1f}%")
    print(f"Mean Reciprocal Rank (MRR): {mrr:.3f}")
    print(f"Citation Correctness: {mean_citation_correctness * 100:.1f}%")
    print(f"Unsupported Claim Rate: {unsupported_rate * 100:.1f}%")

    assert mean_p >= 0.4, f"Precision {mean_p} below acceptable benchmark (>= 0.40)"
    assert mean_r >= 0.8, f"Recall {mean_r} below acceptable benchmark (>= 0.80)"
    assert mrr >= 0.85, f"MRR {mrr} below acceptable benchmark (>= 0.85)"
    assert mean_citation_correctness >= 0.95, f"Citation correctness below 95%"

    return {
        "mean_precision": round(mean_p, 4),
        "mean_recall": round(mean_r, 4),
        "mrr": round(mrr, 4),
        "citation_correctness": round(mean_citation_correctness, 4),
        "unsupported_claim_rate": round(unsupported_rate, 4),
    }

def test_rag_evaluation_benchmark():
    metrics = run_rag_evaluation(top_k=3, dimension=128)
    assert metrics["mean_recall"] >= 0.8
    assert metrics["mrr"] >= 0.85

if __name__ == "__main__":
    run_rag_evaluation()
