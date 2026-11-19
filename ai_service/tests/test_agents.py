import pytest
import sys
import os

# Ensure ai_service is on sys.path
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), '../..')))

from ai_service.agents.specialized import (
    VesselIntelligenceAgent,
    IdentityResolutionAgent,
    CorporateIntelligenceAgent,
    CrewResearchAgent,
    MaritimeJurisdictionAgent,
    IuuResearchAgent,
    EvidenceAnalysisAgent,
    ResearchAssistantAgent
)
from ai_service.agents.orchestrator import OrchestratorAgent

def test_vessel_intelligence_agent():
    agent = VesselIntelligenceAgent()
    context = {
        "vessels": [
            {"name": "FV PANAMA RUNNER", "flag_country_code": "PA", "imo_number": "0"},
            {"name": "KM JAYA ABADI", "flag_country_code": "ID", "imo_number": "9123456"}
        ]
    }
    result = agent.analyze("Check vessel flags and IMO", context)
    assert result["agent"] == "VesselIntelligenceAgent"
    assert result["observations_count"] >= 2  # FoC + missing IMO on vessel 1
    types = [f["type"] for f in result["findings"]]
    assert "flag_of_convenience" in types
    assert "missing_imo" in types

def test_identity_resolution_agent():
    agent = IdentityResolutionAgent()
    context = {
        "conflicts": [
            {
                "conflicting_identifier_type": "mmsi",
                "conflicting_identifier_value": "525009988",
                "conflict_score": 85
            }
        ]
    }
    result = agent.analyze("Audit MMSI collisions", context)
    assert result["agent"] == "IdentityResolutionAgent"
    assert len(result["findings"]) == 1
    assert result["findings"][0]["severity"] == "critical"
    assert "potential_identity_fraud" in result["findings"][0]["type"]

def test_corporate_intelligence_agent():
    agent = CorporateIntelligenceAgent()
    context = {
        "companies": [
            {"name": "Oceanic Sheltering Inc", "jurisdiction_country_code": "VG", "company_type": "single_vessel_spv"}
        ]
    }
    result = agent.analyze("Investigate beneficial ownership", context)
    assert result["agent"] == "CorporateIntelligenceAgent"
    types = [f["type"] for f in result["findings"]]
    assert "beneficial_ownership_obscurity" in types
    assert "single_purpose_vehicle" in types

def test_crew_research_agent():
    agent = CrewResearchAgent()
    context = {
        "crew_records": [
            {
                "vessel_name": "FV RED TIDE",
                "ilo_indicators": ["retention_of_documents", "withholding_of_wages"]
            }
        ]
    }
    result = agent.analyze("Check crew labour violations", context)
    assert result["agent"] == "CrewResearchAgent"
    assert len(result["findings"]) == 1
    assert result["findings"][0]["severity"] == "critical"

def test_maritime_jurisdiction_agent():
    agent = MaritimeJurisdictionAgent()
    context = {
        "jurisdictions": [
            {"area_name": "North Natuna Overlapping Shelf", "is_disputed": True, "legal_status": "disputed"}
        ]
    }
    result = agent.analyze("Check EEZ boundary disputes", context)
    assert result["agent"] == "MaritimeJurisdictionAgent"
    assert len(result["findings"]) == 1
    assert result["findings"][0]["type"] == "jurisdiction_violation"

def test_iuu_research_agent():
    agent = IuuResearchAgent()
    context = {
        "iuu_listings": [
            {
                "listing_organization": "WCPFC",
                "vessel_name": "HAI SHENG 18",
                "listing_reason": "Unlicensed transshipment of bigeye tuna"
            }
        ]
    }
    result = agent.analyze("Scan RFMO IUU blacklist", context)
    assert result["agent"] == "IuuResearchAgent"
    assert len(result["findings"]) == 1
    assert result["findings"][0]["severity"] == "critical"

def test_evidence_analysis_agent():
    agent = EvidenceAnalysisAgent()
    context = {
        "evidences": [
            {"id": "ev-1", "title": "Port Inspection Report", "verification_status": "verified"},
            {"id": "ev-2", "title": "Anonymous Tip Log", "verification_status": "pending"}
        ]
    }
    result = agent.analyze("Audit evidence integrity", context)
    assert result["agent"] == "EvidenceAnalysisAgent"
    assert result["observations_count"] == 2
    assert "Verified: 1" in result["synthesis"]

def test_research_assistant_agent_synthesis():
    agent = ResearchAssistantAgent()
    findings = [
        {"title": "MMSI Collision Detected", "severity": "critical", "summary": "MMSI 525000001 transmitting on 2 hulls."}
    ]
    report = agent.synthesize("Evaluate vessel", [{"findings": findings}])
    assert "Intelligence Assessment Report" in report
    assert "MMSI Collision Detected" in report
    assert "Recommended Investigator Next Steps" in report
    assert "Disclaimer" in report

def test_orchestrator_agent_end_to_end():
    orchestrator = OrchestratorAgent()
    context = {
        "vessels": [{"name": "FV BINTANG TIMUR", "flag_country_code": "PA", "imo_number": ""}],
        "companies": [{"name": "Secrecy Shell Co", "jurisdiction_country_code": "KY"}],
        "evidences": [{"id": "ev-01", "title": "Bilateral Inspection Doc", "verification_status": "verified"}]
    }
    res = orchestrator.process_query("Investigate corporate and vessel non-compliance", context, agent_mode="orchestrator")
    assert "content" in res
    assert "citations" in res
    assert "reasoning_trace" in res
    assert "findings" in res
    assert len(res["reasoning_trace"]["steps"]) >= 3
    assert len(res["citations"]) >= 1
    assert res["citations"][0]["evidence_id"] == "ev-01"

if __name__ == "__main__":
    test_vessel_intelligence_agent()
    test_identity_resolution_agent()
    test_corporate_intelligence_agent()
    test_crew_research_agent()
    test_maritime_jurisdiction_agent()
    test_iuu_research_agent()
    test_evidence_analysis_agent()
    test_research_assistant_agent_synthesis()
    test_orchestrator_agent_end_to_end()
    print("All 9 Agent Tests Passed Successfully!")
