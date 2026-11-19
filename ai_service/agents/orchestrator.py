from typing import Dict, Any, List
from .specialized import (
    VesselIntelligenceAgent,
    IdentityResolutionAgent,
    CorporateIntelligenceAgent,
    CrewResearchAgent,
    MaritimeJurisdictionAgent,
    IuuResearchAgent,
    EvidenceAnalysisAgent,
    ResearchAssistantAgent
)
from ..rag.grounding import GroundingGuard

class OrchestratorAgent:
    """
    Coordinates multi-agent investigative analysis.
    Deconstructs investigator queries, delegates to domain specialists,
    evaluates evidentiary grounding, and synthesizes structured findings.
    """
    def __init__(self):
        self.vessel_agent = VesselIntelligenceAgent()
        self.identity_agent = IdentityResolutionAgent()
        self.corporate_agent = CorporateIntelligenceAgent()
        self.crew_agent = CrewResearchAgent()
        self.jurisdiction_agent = MaritimeJurisdictionAgent()
        self.iuu_agent = IuuResearchAgent()
        self.evidence_agent = EvidenceAnalysisAgent()
        self.assistant_agent = ResearchAssistantAgent()

    def process_query(self, prompt: str, context: Dict[str, Any], agent_mode: str = "orchestrator") -> Dict[str, Any]:
        reasoning_trace = []
        agent_results = []
        all_findings = []

        # Determine which agents to execute based on mode and query keywords
        agents_to_run = []
        q_lower = prompt.lower()

        if agent_mode == "orchestrator":
            reasoning_trace.append({
                "step": 1,
                "action": "Query Intent Deconstruction",
                "details": f"Analyzing query '{prompt}' across all 8 investigative domains."
            })

            # Check keywords or run full scan if general
            run_all = any(w in q_lower for w in ["dossier", "all", "investigate", "profile", "overview", "analyze", "report"]) or len(q_lower.split()) < 4

            if run_all or any(w in q_lower for w in ["vessel", "ship", "boat", "imo", "mmsi", "flag"]):
                agents_to_run.append(self.vessel_agent)
            if run_all or any(w in q_lower for w in ["conflict", "identity", "duplicate", "alias", "fraud"]):
                agents_to_run.append(self.identity_agent)
            if run_all or any(w in q_lower for w in ["company", "corporate", "owner", "ubo", "shareholder", "holding"]):
                agents_to_run.append(self.corporate_agent)
            if run_all or any(w in q_lower for w in ["crew", "forced labour", "labour", "manning", "skipper", "ilo"]):
                agents_to_run.append(self.crew_agent)
            if run_all or any(w in q_lower for w in ["boundary", "eez", "jurisdiction", "natuna", "disputed", "waters"]):
                agents_to_run.append(self.jurisdiction_agent)
            if run_all or any(w in q_lower for w in ["iuu", "illegal", "blacklist", "rfmo", "sanction", "infraction"]):
                agents_to_run.append(self.iuu_agent)
            
            # Evidence agent always runs to audit evidentiary chain
            agents_to_run.append(self.evidence_agent)

        elif agent_mode == "vessel":
            agents_to_run = [self.vessel_agent, self.identity_agent, self.evidence_agent]
        elif agent_mode == "corporate":
            agents_to_run = [self.corporate_agent, self.evidence_agent]
        elif agent_mode == "iuu":
            agents_to_run = [self.iuu_agent, self.evidence_agent]
        elif agent_mode == "crew":
            agents_to_run = [self.crew_agent, self.evidence_agent]
        elif agent_mode == "jurisdiction":
            agents_to_run = [self.jurisdiction_agent, self.evidence_agent]
        else:
            agents_to_run = [self.vessel_agent, self.corporate_agent, self.iuu_agent, self.evidence_agent]

        # Execute agents
        step = 2
        for agent in agents_to_run:
            res = agent.analyze(prompt, context)
            agent_results.append(res)
            reasoning_trace.append({
                "step": step,
                "agent": agent.name,
                "action": f"Executed domain analysis for {agent.domain}",
                "findings_count": len(res.get("findings", [])),
                "summary": res.get("synthesis")
            })
            all_findings.extend(res.get("findings", []))
            step += 1

        # Synthesize final response
        raw_markdown = self.assistant_agent.synthesize(prompt, agent_results)
        sanitized_markdown = GroundingGuard.sanitize_claims(raw_markdown)

        # Extract citations
        available_evidence = context.get("evidences", [])
        citations = GroundingGuard.extract_citations(sanitized_markdown, available_evidence)

        reasoning_trace.append({
            "step": step,
            "agent": "ResearchAssistantAgent",
            "action": "Synthesized evidence-grounded research output",
            "citations_linked": len(citations),
            "findings_surfaced": len(all_findings)
        })

        return {
            "content": sanitized_markdown,
            "citations": citations,
            "reasoning_trace": {
                "total_steps": len(reasoning_trace),
                "agents_invoked": [a.name for a in agents_to_run],
                "steps": reasoning_trace
            },
            "findings": all_findings,
            "tokens_used": len(prompt.split()) + len(sanitized_markdown.split())
        }
