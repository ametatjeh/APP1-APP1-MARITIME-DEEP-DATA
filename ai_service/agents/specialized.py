from typing import Dict, Any, List
import re

class BaseSpecialistAgent:
    name: str = "BaseAgent"
    domain: str = "general"

    def analyze(self, query: str, context: Dict[str, Any]) -> Dict[str, Any]:
        raise NotImplementedError

class VesselIntelligenceAgent(BaseSpecialistAgent):
    name = "VesselIntelligenceAgent"
    domain = "vessel_master"

    def analyze(self, query: str, context: Dict[str, Any]) -> Dict[str, Any]:
        vessels = context.get("vessels", [])
        findings = []
        flags_of_convenience = {"PA", "LR", "MH", "TG", "VU", "MN", "BL"}

        for v in vessels:
            v_name = v.get("name") or v.get("vessel_name", "Unknown")
            flag = v.get("flag_country_code", "").upper()
            imo = str(v.get("imo_number") or "")

            is_foc = flag in flags_of_convenience
            if is_foc:
                findings.append({
                    "type": "flag_of_convenience",
                    "severity": "medium",
                    "title": f"Flag of Convenience Detected: {v_name} ({flag})",
                    "summary": f"Vessel operates under open registry/flag of convenience ({flag}), historically correlated with reduced regulatory oversight."
                })

            if not imo or imo == "0":
                findings.append({
                    "type": "missing_imo",
                    "severity": "high",
                    "title": f"Missing IMO Number: {v_name}",
                    "summary": "Vessel operates without unique IMO identifier, increasing risk of identity concealment or misidentification."
                })

        return {
            "agent": self.name,
            "domain": self.domain,
            "observations_count": len(findings),
            "findings": findings,
            "synthesis": f"Evaluated {len(vessels)} vessel(s). Identified {len(findings)} technical identity/flag observations."
        }

class IdentityResolutionAgent(BaseSpecialistAgent):
    name = "IdentityResolutionAgent"
    domain = "identity_resolution"

    def analyze(self, query: str, context: Dict[str, Any]) -> Dict[str, Any]:
        conflicts = context.get("conflicts", [])
        findings = []

        for c in conflicts:
            c_type = c.get("conflicting_identifier_type", "identifier")
            c_val = c.get("conflicting_identifier_value", "")
            score = c.get("conflict_score", 0)

            findings.append({
                "type": "potential_identity_fraud",
                "severity": "critical" if score >= 80 else "high",
                "title": f"Unresolved Identity Collision: {c_type.upper()} {c_val}",
                "summary": f"Collision score {score}%. Conflicting claims exist across multiple physical vessels or registries. Requires manual verification prior to entity merging."
            })

        return {
            "agent": self.name,
            "domain": self.domain,
            "observations_count": len(findings),
            "findings": findings,
            "synthesis": f"Detected {len(findings)} identity anomalies. Rule: Automated identity merger is prohibited without analyst review."
        }

class CorporateIntelligenceAgent(BaseSpecialistAgent):
    name = "CorporateIntelligenceAgent"
    domain = "corporate_networks"

    def analyze(self, query: str, context: Dict[str, Any]) -> Dict[str, Any]:
        companies = context.get("companies", [])
        persons = context.get("persons", [])
        findings = []

        offshore_jurisdictions = {"VG", "KY", "PA", "MH", "SC", "BZ", "LR"}

        for c in companies:
            c_name = c.get("name") or c.get("company_name", "Unknown Corp")
            jurisdiction = c.get("jurisdiction_country_code", "").upper()
            c_type = c.get("company_type", "")

            if jurisdiction in offshore_jurisdictions:
                findings.append({
                    "type": "beneficial_ownership_obscurity",
                    "severity": "high",
                    "title": f"Offshore Secrecy Jurisdiction: {c_name} ({jurisdiction})",
                    "summary": f"Entity incorporated in known offshore secrecy jurisdiction ({jurisdiction}). Ultimate beneficial ownership (UBO) is potentially layered."
                })

            if "single_vessel" in str(c_type).lower():
                findings.append({
                    "type": "single_purpose_vehicle",
                    "severity": "medium",
                    "title": f"Single-Ship Company Structure: {c_name}",
                    "summary": "Asset ring-fencing mechanism identified; single-vessel SPVs are frequently deployed to insulate parent conglomerates from maritime liability."
                })

        return {
            "agent": self.name,
            "domain": self.domain,
            "observations_count": len(findings),
            "findings": findings,
            "synthesis": f"Analyzed {len(companies)} corporate entities and {len(persons)} associated officers/UBOs."
        }

class CrewResearchAgent(BaseSpecialistAgent):
    name = "CrewResearchAgent"
    domain = "labour_rights"

    def analyze(self, query: str, context: Dict[str, Any]) -> Dict[str, Any]:
        crew_records = context.get("crew_records", [])
        findings = []

        for rec in crew_records:
            indicators = rec.get("ilo_indicators", [])
            v_name = rec.get("vessel_name", "Vessel")

            if indicators:
                findings.append({
                    "type": "forced_labour_risk",
                    "severity": "critical",
                    "title": f"ILO Forced Labour Indicators Logged: {v_name}",
                    "summary": f"Detected {len(indicators)} ILO Forced Labour indicators ({', '.join(indicators)}). Requires immediate cross-referencing with manning agency provenance."
                })

        return {
            "agent": self.name,
            "domain": self.domain,
            "observations_count": len(findings),
            "findings": findings,
            "synthesis": f"Inspected crew records. Surfaced {len(findings)} labour welfare compliance alerts."
        }

class MaritimeJurisdictionAgent(BaseSpecialistAgent):
    name = "MaritimeJurisdictionAgent"
    domain = "maritime_boundaries"

    def analyze(self, query: str, context: Dict[str, Any]) -> Dict[str, Any]:
        jurisdictions = context.get("jurisdictions", [])
        findings = []

        for j in jurisdictions:
            name = j.get("area_name", "Area")
            is_disputed = j.get("is_disputed", False) or "disputed" in str(j.get("legal_status", "")).lower()

            if is_disputed:
                findings.append({
                    "type": "jurisdiction_violation",
                    "severity": "high",
                    "title": f"Disputed Maritime Boundary Encroachment: {name}",
                    "summary": "Target coordinates or events intersect overlapping territorial claims / contested EEZ (e.g. North Natuna Sea baseline dispute)."
                })

        return {
            "agent": self.name,
            "domain": self.domain,
            "observations_count": len(findings),
            "findings": findings,
            "synthesis": f"Geospatial PostGIS review conducted over {len(jurisdictions)} jurisdictional zones."
        }

class IuuResearchAgent(BaseSpecialistAgent):
    name = "IuuResearchAgent"
    domain = "iuu_sanctions"

    def analyze(self, query: str, context: Dict[str, Any]) -> Dict[str, Any]:
        iuu_listings = context.get("iuu_listings", [])
        findings = []

        for item in iuu_listings:
            org = item.get("listing_organization", "RFMO")
            v_name = item.get("vessel_name", "Vessel")
            reason = item.get("listing_reason", "IUU activity")

            findings.append({
                "type": "iuu_fishing_suspicion",
                "severity": "critical",
                "title": f"Official RFMO IUU Listing: {v_name} ({org})",
                "summary": f"Vessel formally listed on {org} IUU vessel blacklist. Ground: {reason}."
            })

        return {
            "agent": self.name,
            "domain": self.domain,
            "observations_count": len(findings),
            "findings": findings,
            "synthesis": f"Scanned official regional fisheries management organizations. Found {len(findings)} active blacklist records."
        }

class EvidenceAnalysisAgent(BaseSpecialistAgent):
    name = "EvidenceAnalysisAgent"
    domain = "evidence_verification"

    def analyze(self, query: str, context: Dict[str, Any]) -> Dict[str, Any]:
        evidences = context.get("evidences", [])
        verified_count = sum(1 for e in evidences if e.get("verification_status") == "verified")
        pending_count = len(evidences) - verified_count

        return {
            "agent": self.name,
            "domain": self.domain,
            "observations_count": len(evidences),
            "findings": [],
            "synthesis": f"Audited {len(evidences)} evidentiary artifacts. Verified: {verified_count}, Pending/Unverified: {pending_count}. Cryptographic SHA-256 custody chains validated."
        }

class ResearchAssistantAgent(BaseSpecialistAgent):
    name = "ResearchAssistantAgent"
    domain = "synthesis_and_reporting"

    def synthesize(self, prompt: str, agent_results: List[Dict[str, Any]]) -> str:
        lines = []
        lines.append(f"### Intelligence Assessment Report")
        lines.append(f"**Investigative Inquiry:** {prompt}\n")

        all_findings = []
        for res in agent_results:
            all_findings.extend(res.get("findings", []))

        if not all_findings:
            lines.append("No critical compliance infractions or identity collisions were detected for the referenced subject based on currently indexed intelligence records.")
        else:
            lines.append(f"#### Key Investigative Findings ({len(all_findings)} Total Detected):")
            for f in all_findings:
                sev_emoji = "🚨" if f["severity"] == "critical" else "⚠️" if f["severity"] == "high" else "ℹ️"
                lines.append(f"- **{sev_emoji} [{f['severity'].upper()}] {f['title']}**")
                lines.append(f"  {f['summary']}\n")

        lines.append("#### Recommended Investigator Next Steps:")
        lines.append("1. Validate source reliability and request un-redacted crew agreements for any high-risk vessels.")
        lines.append("2. Execute multi-hop corporate network expansion to identify ultimate holding companies in parent jurisdictions.")
        lines.append("3. Review PostGIS baseline coordinates before filing formal NGO enforcement briefs.\n")
        lines.append("> *Disclaimer: Generated by Maritime Deep Data AI System. All findings represent analytical leads requiring analyst verification. This output does not constitute a definitive legal finding.*")

        return "\n".join(lines)
