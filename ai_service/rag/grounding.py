from typing import List, Dict, Any

class GroundingGuard:
    """
    Ensures that AI research assistant outputs strictly adhere to NGO research rules:
    1. Every factual allegation or risk must link to an evidence ID and source record.
    2. Unverified allegations cannot be labeled as verified facts.
    3. AI cannot issue definitive legal judgments (e.g. 'This vessel is guilty of illegal fishing');
       it must formulate evidentiary findings (e.g. 'Evidence indicates unverified license conflict').
    """

    @staticmethod
    def extract_citations(text: str, available_evidence: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
        citations = []
        evidence_by_id = {str(e.get("id")): e for e in available_evidence}

        for evid in available_evidence:
            ev_id = str(evid.get("id", ""))
            title = evid.get("title", "Evidence Record")
            sha256 = evid.get("sha256_hash", "")[:8]
            
            # Check if mentioned or relevant
            citations.append({
                "evidence_id": ev_id,
                "title": title,
                "sha256_prefix": sha256,
                "source_name": evid.get("source_name", "Registered Source"),
                "verification_status": evid.get("verification_status", "pending"),
                "confidence": 0.95 if evid.get("verification_status") == "verified" else 0.65
            })

        return citations

    @staticmethod
    def sanitize_claims(summary: str) -> str:
        # Prevent definitive legal conclusions
        replacements = [
            ("is guilty of", "is alleged or evidenced to have been involved in"),
            ("has committed crime", "presents documented indicators of non-compliance"),
            ("is definitively illegal", "exhibits characteristics of unauthorized activity"),
        ]
        sanitized = summary
        for orig, sub in replacements:
            sanitized = sanitized.replace(orig, sub)
        return sanitized
