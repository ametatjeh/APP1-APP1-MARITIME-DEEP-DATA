<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2">
          <h3 class="text-base font-bold text-white">GIS Research Map & Spatial Analytics</h3>
          <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-cyan-950 border border-cyan-800 text-cyan-300">
            POSTGIS 3.6 • RFC 7946
          </span>
        </div>
        <p class="text-xs text-slate-400 mt-0.5">
          Multi-layer spatial analytics across EEZ boundaries, contested archipelagic waters, research polygons, and IUU incidents.
        </p>
      </div>

      <div class="flex items-center gap-2">
        <button
          @click="showCreatePolygonModal = true"
          class="px-3.5 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-medium transition-colors shadow-lg shadow-cyan-950/40 flex items-center gap-1.5"
        >
          <span>➕</span>
          <span>Create Research Polygon</span>
        </button>
      </div>
    </div>

    <!-- Map Workspace Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
      <!-- Left: Interactive Map Container -->
      <div class="lg:col-span-3 space-y-4">
        <!-- Layer Controls Bar -->
        <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 flex flex-wrap items-center justify-between gap-4 text-xs font-mono">
          <div class="flex flex-wrap items-center gap-4">
            <span class="text-slate-400 font-bold">Active Layers:</span>
            <label class="flex items-center gap-1.5 text-cyan-300 cursor-pointer">
              <input v-model="visibleLayers.jurisdictions" @change="renderLayers" type="checkbox" class="rounded bg-slate-950 border-slate-800 text-cyan-600" />
              <span>🗺️ EEZ / Jurisdictions</span>
            </label>
            <label class="flex items-center gap-1.5 text-amber-300 cursor-pointer">
              <input v-model="visibleLayers.polygons" @change="renderLayers" type="checkbox" class="rounded bg-slate-950 border-slate-800 text-amber-600" />
              <span>🟥 Research Polygons (AOI)</span>
            </label>
            <label class="flex items-center gap-1.5 text-emerald-300 cursor-pointer">
              <input v-model="visibleLayers.events" @change="renderLayers" type="checkbox" class="rounded bg-slate-950 border-slate-800 text-emerald-600" />
              <span>⚓ Spatial Events</span>
            </label>
            <label class="flex items-center gap-1.5 text-rose-300 cursor-pointer">
              <input v-model="visibleLayers.iuu" @change="renderLayers" type="checkbox" class="rounded bg-slate-950 border-slate-800 text-rose-600" />
              <span>🚨 IUU Infractions</span>
            </label>
          </div>

          <div class="flex items-center gap-2">
            <button
              @click="resetMapView"
              class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 text-[11px] transition-colors"
            >
              Center Indonesia / Natuna
            </button>
          </div>
        </div>

        <!-- Leaflet Map Container -->
        <div class="relative rounded-2xl bg-slate-900 border border-slate-800 overflow-hidden shadow-2xl h-[560px]">
          <div id="research-map" class="w-full h-full z-0"></div>

          <!-- Map Loading Indicator -->
          <div
            v-if="mapLoading"
            class="absolute inset-0 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center z-10 text-xs text-slate-300 font-mono"
          >
            Fetching PostGIS GeoJSON layers...
          </div>

          <!-- Bottom Left Coordinates Indicator -->
          <div class="absolute bottom-3 left-3 z-10 bg-slate-950/90 border border-slate-800/80 px-3 py-1.5 rounded-lg text-[11px] font-mono text-slate-400">
            <span v-if="clickedCoords">
              Clicked: <strong class="text-cyan-300">{{ clickedCoords.lat.toFixed(4) }}°N, {{ clickedCoords.lng.toFixed(4) }}°E</strong>
            </span>
            <span v-else>Click map to execute PostGIS spatial containment query</span>
          </div>
        </div>
      </div>

      <!-- Right: Spatial Query & Feature Inspector -->
      <div class="space-y-4">
        <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 space-y-3">
          <div class="flex items-center justify-between border-b border-slate-800 pb-2">
            <h4 class="text-xs font-bold text-white uppercase font-mono flex items-center gap-1.5">
              <span>🧭</span>
              <span>Spatial Containment Inspector</span>
            </h4>
            <span v-if="inspecting" class="text-[10px] text-cyan-400 animate-pulse font-mono">Querying...</span>
          </div>

          <div v-if="!selectedFeature && !spatialQueryResult" class="text-xs text-slate-500 py-8 text-center italic">
            Click on any marker, polygon, or map coordinate to inspect spatial containment and intersecting jurisdictions.
          </div>

          <!-- Map Click Containment Result -->
          <div v-if="spatialQueryResult" class="space-y-3 text-xs">
            <div class="p-2.5 rounded bg-slate-950 border border-slate-800 font-mono text-[11px] space-y-1">
              <span class="text-slate-400 block font-semibold">Evaluated Coordinates:</span>
              <span class="text-cyan-300">{{ spatialQueryResult.coordinates?.latitude.toFixed(4) }}°N, {{ spatialQueryResult.coordinates?.longitude.toFixed(4) }}°E</span>
            </div>

            <div class="space-y-1">
              <span class="text-[11px] font-bold text-slate-400 uppercase font-mono block">Intersecting Jurisdictions:</span>
              <div v-if="!spatialQueryResult.containing_jurisdictions || spatialQueryResult.containing_jurisdictions.length === 0" class="text-slate-500 text-[11px] italic">
                Point lies in International High Seas (outside claimed EEZ/Archipelagic zones).
              </div>
              <div v-else class="space-y-1">
                <div
                  v-for="j in spatialQueryResult.containing_jurisdictions"
                  :key="j.id"
                  class="p-2 rounded bg-cyan-950/40 border border-cyan-800/60 text-[11px]"
                >
                  <div class="font-bold text-cyan-300">{{ j.area_name }}</div>
                  <div class="text-slate-400 font-mono text-[10px]">{{ j.jurisdiction_type }} • {{ j.legal_status }}</div>
                </div>
              </div>
            </div>

            <!-- Intersecting Research Polygons -->
            <div v-if="spatialQueryResult.containing_research_polygons && spatialQueryResult.containing_research_polygons.length" class="space-y-1">
              <span class="text-[11px] font-bold text-slate-400 uppercase font-mono block">Inside Research AOI:</span>
              <div
                v-for="rp in spatialQueryResult.containing_research_polygons"
                :key="rp.id"
                class="p-2 rounded bg-amber-950/40 border border-amber-800/60 text-[11px]"
              >
                <div class="font-bold text-amber-300">{{ rp.name }}</div>
                <div class="text-slate-400 text-[10px]">{{ rp.category }}</div>
              </div>
            </div>

            <!-- Nearby Events within 100km -->
            <div v-if="spatialQueryResult.nearby_events_within_100km && spatialQueryResult.nearby_events_within_100km.length" class="space-y-1">
              <span class="text-[11px] font-bold text-slate-400 uppercase font-mono block">Nearby Events (&lt;100km):</span>
              <div
                v-for="ne in spatialQueryResult.nearby_events_within_100km"
                :key="ne.id"
                class="p-2 rounded bg-slate-950 border border-slate-800 text-[11px]"
              >
                <div class="font-bold text-white">{{ ne.title }}</div>
                <div class="text-slate-400 text-[10px]">{{ ne.event_type }} (Vessel: {{ ne.vessel?.name || 'N/A' }})</div>
              </div>
            </div>
          </div>

          <!-- Feature Inspector Card -->
          <div v-if="selectedFeature" class="space-y-2 text-xs border-t border-slate-800 pt-3">
            <span class="text-[11px] font-bold text-slate-400 uppercase font-mono block">Selected Object Dossier:</span>
            <div class="p-3 rounded-lg bg-slate-950 border border-slate-800 space-y-1.5 font-mono text-[11px]">
              <div class="font-bold text-white text-xs">{{ selectedFeature.name || selectedFeature.title }}</div>
              <div class="text-slate-400">Type: <span class="text-cyan-300">{{ selectedFeature.type }}</span></div>
              <div v-if="selectedFeature.vessel_name" class="text-slate-400">Vessel: <span class="text-emerald-300">{{ selectedFeature.vessel_name }} ({{ selectedFeature.vessel_flag || 'N/A' }})</span></div>
              <div v-if="selectedFeature.severity" class="text-slate-400">Severity: <span class="text-rose-400 uppercase">{{ selectedFeature.severity }}</span></div>
              <div v-if="selectedFeature.description" class="text-slate-300 font-sans text-xs pt-1">{{ selectedFeature.description }}</div>
            </div>
          </div>
        </div>

        <!-- Spatial Stats Card -->
        <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 space-y-2 text-xs font-mono">
          <span class="text-slate-400 block font-bold">Spatial Database Summary</span>
          <div class="space-y-1 text-[11px]">
            <div class="flex justify-between text-slate-400">
              <span>Jurisdiction Polygons:</span>
              <strong class="text-cyan-300">{{ layersData?.layers?.jurisdictions?.features?.length || 0 }}</strong>
            </div>
            <div class="flex justify-between text-slate-400">
              <span>Research AOIs:</span>
              <strong class="text-amber-300">{{ layersData?.layers?.research_polygons?.features?.length || 0 }}</strong>
            </div>
            <div class="flex justify-between text-slate-400">
              <span>IUU Infractions:</span>
              <strong class="text-rose-400">{{ layersData?.layers?.iuu_infractions?.features?.length || 0 }}</strong>
            </div>
            <div class="flex justify-between text-slate-400">
              <span>Spatial Sightings:</span>
              <strong class="text-emerald-300">{{ layersData?.layers?.spatial_events?.features?.length || 0 }}</strong>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Polygon Modal -->
    <div v-if="showCreatePolygonModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-slate-900 border border-slate-800 rounded-xl max-w-lg w-full p-5 space-y-4">
        <h4 class="text-sm font-bold text-white">Create Custom Research AOI Polygon</h4>
        <form @submit.prevent="createPolygon" class="space-y-3 text-xs">
          <div>
            <label class="block text-slate-400 mb-1">Area of Interest Name *</label>
            <input v-model="newPolygon.name" required type="text" placeholder="e.g. Arafura Sea Southern Transshipment Corridor" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white" />
          </div>

          <div>
            <label class="block text-slate-400 mb-1">Category</label>
            <select v-model="newPolygon.category" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white">
              <option value="high_risk_iuu_zone">High Risk IUU Zone</option>
              <option value="transshipment_corridor">Transshipment Corridor</option>
              <option value="patrol_area">Patrol Area</option>
              <option value="marine_protected_area">Marine Protected Area</option>
              <option value="investigation_aoi">Investigation AOI</option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-slate-400 mb-1">Min Longitude / Max Longitude</label>
              <div class="flex gap-1">
                <input v-model.number="bboxForm.minLon" type="number" step="0.1" placeholder="107.0" class="w-1/2 px-2 py-1.5 bg-slate-950 border border-slate-800 rounded text-white font-mono" />
                <input v-model.number="bboxForm.maxLon" type="number" step="0.1" placeholder="110.0" class="w-1/2 px-2 py-1.5 bg-slate-950 border border-slate-800 rounded text-white font-mono" />
              </div>
            </div>

            <div>
              <label class="block text-slate-400 mb-1">Min Latitude / Max Latitude</label>
              <div class="flex gap-1">
                <input v-model.number="bboxForm.minLat" type="number" step="0.1" placeholder="3.0" class="w-1/2 px-2 py-1.5 bg-slate-950 border border-slate-800 rounded text-white font-mono" />
                <input v-model.number="bboxForm.maxLat" type="number" step="0.1" placeholder="5.5" class="w-1/2 px-2 py-1.5 bg-slate-950 border border-slate-800 rounded text-white font-mono" />
              </div>
            </div>
          </div>

          <div>
            <label class="block text-slate-400 mb-1">Analytical Notes / Description</label>
            <textarea v-model="newPolygon.description" rows="2" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white"></textarea>
          </div>

          <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
            <button type="button" @click="showCreatePolygonModal = false" class="px-3 py-1.5 rounded bg-slate-800 text-slate-300">Cancel</button>
            <button type="submit" class="px-3 py-1.5 rounded bg-cyan-600 text-white">Save Polygon Layer</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import apiClient from '@/api/client';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const mapLoading = ref(true);
const layersData = ref<any>(null);
const selectedFeature = ref<any>(null);
const spatialQueryResult = ref<any>(null);
const inspecting = ref(false);
const clickedCoords = ref<{ lat: number; lng: number } | null>(null);

let map: L.Map | null = null;
let layerGroup: L.LayerGroup | null = null;

const visibleLayers = ref({
  jurisdictions: true,
  polygons: true,
  events: true,
  iuu: true,
});

const showCreatePolygonModal = ref(false);
const newPolygon = ref({
  name: '',
  category: 'high_risk_iuu_zone',
  description: '',
});

const bboxForm = ref({
  minLon: 107.0,
  maxLon: 110.0,
  minLat: 3.0,
  maxLat: 5.5,
});

function initMap() {
  const container = document.getElementById('research-map');
  if (!container || map) return;

  // Center on Indonesian waters / Natuna Sea
  map = L.map('research-map', {
    center: [3.5, 108.5],
    zoom: 6,
    zoomControl: false,
  });

  L.control.zoom({ position: 'topright' }).addTo(map);

  // Tactical Dark CartoDB basemap
  L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
    attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
    subdomains: 'abcd',
    maxZoom: 19,
  }).addTo(map);

  layerGroup = L.layerGroup().addTo(map);

  // Map Click Listener for PostGIS Spatial Containment Query
  map.on('click', async (e: L.LeafletMouseEvent) => {
    clickedCoords.value = { lat: e.latlng.lat, lng: e.latlng.lng };
    await runPointQuery(e.latlng.lng, e.latlng.lat);
  });
}

async function loadMapLayers() {
  mapLoading.value = true;
  try {
    const res = await apiClient.get('/gis/layers');
    layersData.value = res.data;
    renderLayers();
  } catch (err) {
    console.error('Failed to load GIS layers', err);
  } finally {
    mapLoading.value = false;
  }
}

function renderLayers() {
  if (!map || !layerGroup || !layersData.value) return;

  layerGroup.clearLayers();

  const layers = layersData.value.layers;

  // 1. Jurisdictions
  if (visibleLayers.value.jurisdictions && layers.jurisdictions) {
    L.geoJSON(layers.jurisdictions, {
      style: (feature) => {
        const isDisputed = feature?.properties?.legal_status === 'disputed';
        return {
          color: isDisputed ? '#f59e0b' : '#06b6d4',
          weight: 1.5,
          opacity: 0.8,
          fillColor: isDisputed ? '#f59e0b' : '#0891b2',
          fillOpacity: 0.12,
          dashArray: isDisputed ? '4, 4' : undefined,
        };
      },
      onEachFeature: (feature, layer) => {
        layer.on('click', (e) => {
          L.DomEvent.stopPropagation(e);
          selectedFeature.value = feature.properties;
        });
      },
    }).addTo(layerGroup);
  }

  // 2. Custom Research Polygons
  if (visibleLayers.value.polygons && layers.research_polygons) {
    L.geoJSON(layers.research_polygons, {
      style: () => ({
        color: '#f97316',
        weight: 2,
        dashArray: '5, 5',
        fillColor: '#ea580c',
        fillOpacity: 0.2,
      }),
      onEachFeature: (feature, layer) => {
        layer.on('click', (e) => {
          L.DomEvent.stopPropagation(e);
          selectedFeature.value = feature.properties;
        });
      },
    }).addTo(layerGroup);
  }

  // 3. Spatial Events (Boardings / Seizures)
  if (visibleLayers.value.events && layers.spatial_events) {
    L.geoJSON(layers.spatial_events, {
      pointToLayer: (feature, latlng) => {
        return L.circleMarker(latlng, {
          radius: 6,
          fillColor: '#10b981',
          color: '#ffffff',
          weight: 1.5,
          opacity: 1,
          fillOpacity: 0.85,
        });
      },
      onEachFeature: (feature, layer) => {
        layer.on('click', (e) => {
          L.DomEvent.stopPropagation(e);
          selectedFeature.value = feature.properties;
        });
      },
    }).addTo(layerGroup);
  }

  // 4. IUU Infractions (Red Alert Points)
  if (visibleLayers.value.iuu && layers.iuu_infractions) {
    L.geoJSON(layers.iuu_infractions, {
      pointToLayer: (feature, latlng) => {
        return L.circleMarker(latlng, {
          radius: 7,
          fillColor: '#ef4444',
          color: '#fca5a5',
          weight: 2,
          opacity: 1,
          fillOpacity: 0.9,
        });
      },
      onEachFeature: (feature, layer) => {
        layer.on('click', (e) => {
          L.DomEvent.stopPropagation(e);
          selectedFeature.value = feature.properties;
        });
      },
    }).addTo(layerGroup);
  }
}

async function runPointQuery(lng: number, lat: number) {
  inspecting.value = true;
  try {
    const res = await apiClient.post('/gis/query', { longitude: lng, latitude: lat });
    spatialQueryResult.value = res.data;
  } catch (err) {
    console.error('Failed to run spatial query', err);
  } finally {
    inspecting.value = false;
  }
}

function resetMapView() {
  if (map) {
    map.setView([3.5, 108.5], 6);
  }
}

async function createPolygon() {
  try {
    const geojson = {
      type: 'Polygon',
      coordinates: [
        [
          [bboxForm.value.minLon, bboxForm.value.minLat],
          [bboxForm.value.maxLon, bboxForm.value.minLat],
          [bboxForm.value.maxLon, bboxForm.value.maxLat],
          [bboxForm.value.minLon, bboxForm.value.maxLat],
          [bboxForm.value.minLon, bboxForm.value.minLat],
        ],
      ],
    };

    await apiClient.post('/gis/polygons', {
      name: newPolygon.value.name,
      category: newPolygon.value.category,
      description: newPolygon.value.description,
      geojson,
    });

    showCreatePolygonModal.value = false;
    newPolygon.value = { name: '', category: 'high_risk_iuu_zone', description: '' };
    await loadMapLayers();
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to create polygon.');
  }
}

onMounted(() => {
  initMap();
  loadMapLayers();
});

onUnmounted(() => {
  if (map) {
    map.remove();
    map = null;
  }
});
</script>

<style>
/* Leaflet dark theme customizations */
.leaflet-container {
  background: #020617 !important;
}
.leaflet-bar a {
  background-color: #0f172a !important;
  color: #94a3b8 !important;
  border-bottom: 1px solid #1e293b !important;
}
.leaflet-bar a:hover {
  background-color: #1e293b !important;
  color: #38bdf8 !important;
}
</style>
