<template>
  <div id="gtm-ecommerce-woo-event-inspector" v-show="true" :class="{ minimized }">
    <div class="header">
      <span>GTM Debug Tool</span>
      <div>
        <button class="clear-history" @click="clearHistory">Clear History</button>
        <button class="toggle-size" @click="toggleSize" aria-label="Toggle tool size">_</button>
      </div>
    </div>
    <div class="tabs">
      <div 
        class="tab" 
        :class="{ active: activeTab === 'events' }"
        @click="activeTab = 'events'"
      >
        Events
      </div>
      <div 
        class="tab" 
        :class="{ active: activeTab === 'containers' }"
        @click="activeTab = 'containers'"
      >
        GTM Containers
      </div>
      <div 
        class="tab" 
        :class="{ active: activeTab === 'consent' }"
        @click="activeTab = 'consent'"
      >
        Consent Mode
      </div>
    </div>
    <div class="content">
      <div class="tab-content" :class="{ active: activeTab === 'events' }" id="events">
        <ul id="gtm-ecommerce-woo-event-inspector-list">
          <li 
            v-for="(event, index) in reversedEvents" 
            :key="index"
            style="cursor: pointer; list-style: none; color: black; font-weight: bold; padding-top: 10px;"
          >
            <span @click="toggleEventDetails(index)">{{ event.eventName }}</span>
            <pre v-show="event.isExpanded"><code class="language-json" v-html="highlightJson(event.data)"></code></pre>
          </li>
        </ul>
      </div>
      <div class="tab-content" :class="{ active: activeTab === 'containers' }" id="containers">
        <div v-if="Object.keys(gtmContainers).length === 0" class="no-data-message">
          No containers found
        </div>
        <ul v-else>
          <li 
            v-for="id in Object.keys(gtmContainers)" 
            :key="id"
            style="list-style: none; color: black; font-weight: bold; padding: 10px;"
          >
            <span class="check-icon">✓</span> {{ id }}
          </li>
        </ul>
      </div>
      <div class="tab-content" :class="{ active: activeTab === 'consent' }" id="consent">
        <div class="consent-section">
          <h3>Default State</h3>
          <pre v-if="consentDefault"><code class="language-json" v-html="highlightJson(consentDefault)"></code></pre>
          <div v-else class="no-data-message">No default consent state found</div>
        </div>
        <div class="consent-section">
          <h3>Updated State</h3>
          <pre v-if="consentUpdate"><code class="language-json" v-html="highlightJson(consentUpdate)"></code></pre>
          <div v-else class="no-data-message">No consent updates found</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import hljs from 'highlight.js';
import 'highlight.js/styles/default.css';

export default {
  name: 'EventInspector',
  
  data() {
    return {
      events: [],
      minimized: false,
      dataLayerIndex: 0,
      checkInterval: null,
      activeTab: 'events',
      gtmContainers: {},
      gtmCheckInterval: null,
      consentDefault: null,
      consentUpdate: null
    }
  },

  computed: {
    reversedEvents() {
      return [...this.events].reverse();
    }
  },

  created() {
    this.loadFromStorage();
    this.minimized = sessionStorage.getItem('gtmDatalayerDebuggerMinimized') === 'true';
    this.getGTMContainers();
    this.checkInterval = setInterval(this.checkDataLayer, 100);
    this.gtmCheckInterval = setInterval(this.getGTMContainers, 1000);
  },

  beforeDestroy() {
    if (this.checkInterval) {
      clearInterval(this.checkInterval);
    }
    if (this.gtmCheckInterval) {
      clearInterval(this.gtmCheckInterval);
    }
  },

  methods: {
    loadFromStorage() {
      const stored = sessionStorage.getItem('gtmDatalayerDebugger');
      if (stored) {
        this.events = JSON.parse(stored);
      }
    },

    checkDataLayer() {
      const dataLayer = window.dataLayer || [];
      const currentLength = dataLayer.length;

      if (currentLength > this.dataLayerIndex) {
        const newEvents = dataLayer.slice();
        this.dataLayerIndex = currentLength;

        newEvents.forEach(event => {
          if ('object' === typeof event && event[0] === 'consent') {
            if (event[1] === 'default') {
              this.consentDefault = event[2];
            } else if (event[1] === 'update') {
              this.consentUpdate = event[2];
            }
            return;
          }

          if ((!event.event && !event.ecommerce) || 
              (event.event && event.event.substring(0, 4) === 'gtm.')) {
            return;
          }

          const eventName = this.getEventName(event);
          this.events.push({
            eventName,
            data: event,
            isExpanded: false
          });
        });

        this.saveToStorage();
      }
    },

    getEventName(item) {
      if (!item) return 'Unknown Event';
      
      if (!item.event && item.ecommerce) {
        if (item.ecommerce.purchase) return 'Purchase (UA)';
        if (item.ecommerce.impressions) return 'Product Impression (UA)';
        if (item.ecommerce.detail) return 'Product Detail (UA)';
      }

      const eventMappings = {
        'addToCart': 'addToCart (UA)',
        'productClick': 'productClick (UA)',
        'removeFromCart': 'removeFromCart (UA)',
        'checkout': 'checkout (UA)'
      };

      return eventMappings[item.event] || item.event || 'Unknown Event';
    },

    toggleSize() {
      this.minimized = !this.minimized;
      sessionStorage.setItem('gtmDatalayerDebuggerMinimized', this.minimized);
    },

    clearHistory() {
      this.events = [];
      sessionStorage.setItem('gtmDatalayerDebugger', '[]');
    },

    toggleEventDetails(index) {
      const actualIndex = this.events.length - 1 - index;
      if (actualIndex >= 0 && actualIndex < this.events.length) {
        this.$set(this.events[actualIndex], 'isExpanded', 
          !this.events[actualIndex].isExpanded);
      }
    },

    highlightJson(data) {
      try {
        if (!data) {
          return '';
        }
        const jsonString = JSON.stringify(data, null, 2);
        
        if (!jsonString) {
          return '';
        }
        return hljs.highlight(jsonString, { language: 'json' }).value;
      } catch (e) {
        console.error('Error highlighting JSON:', e);
        return JSON.stringify(data, null, 2) || '';
      }
    },

    saveToStorage() {
      sessionStorage.setItem('gtmDatalayerDebugger', JSON.stringify(this.events));
    },

    getGTMContainers() {
      if (!window.google_tag_manager) return;
      
      const containers = {};
      Object.keys(window.google_tag_manager).forEach(key => {
        if (key.startsWith('GTM-')) {
          containers[key] = true;
        }
      });
      this.gtmContainers = containers;
    }
  }
}
</script>

<style scoped>
#gtm-ecommerce-woo-event-inspector {
    position: fixed;
    bottom: 0;
    right: 0;
    width: 100%;
    max-height: 95%;
    height: 600px;
    background-color: #ffffff;
    font-family: 'Roboto', Arial, sans-serif;
    font-size: 14px;
    z-index: 9999;
    overflow: hidden;
    box-shadow: 0 -4px 6px rgba(0, 0, 0, 0.1);
}

@media (min-width: 768px) {
    #gtm-ecommerce-woo-event-inspector {
        width: 400px;
        bottom: 20px;
        right: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 1px 3px rgba(0, 0, 0, 0.08);
    }
}

#gtm-ecommerce-woo-event-inspector.minimized {
    height: auto;
}

#gtm-ecommerce-woo-event-inspector.minimized .tabs,
#gtm-ecommerce-woo-event-inspector.minimized .content {
    display: none;
}

#gtm-ecommerce-woo-event-inspector .header {
    background-color: #4285f4;
    color: #ffffff;
    padding: 0 15px;
    font-size: 16px;
    font-weight: 500;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

#gtm-ecommerce-woo-event-inspector .content {
    height: calc(100% - 56px);
    overflow-y: auto;
}

#gtm-ecommerce-woo-event-inspector .tabs {
    display: flex;
    background-color: #f1f3f4;
    border-bottom: 1px solid #dadce0;
}

#gtm-ecommerce-woo-event-inspector .tab {
    padding: 12px 16px;
    cursor: pointer;
    color: #5f6368;
    font-weight: 500;
    transition: background-color 0.2s, color 0.2s;
}

#gtm-ecommerce-woo-event-inspector .tab:hover {
    background-color: #e8f0fe;
}

#gtm-ecommerce-woo-event-inspector .tab.active {
    color: #1a73e8;
    border-bottom: 2px solid #1a73e8;
}

#gtm-ecommerce-woo-event-inspector .tab-content {
    display: none;
    height: calc(100% - 49px);
    overflow-y: auto;
    padding: 16px;
}

#gtm-ecommerce-woo-event-inspector .tab-content.active {
    display: block;
}

#gtm-ecommerce-woo-event-inspector .event {
    margin-bottom: 16px;
    border-bottom: 1px solid #e0e0e0;
    padding-bottom: 12px;
}

#gtm-ecommerce-woo-event-inspector .event-name {
    font-weight: 500;
    color: #202124;
    margin-bottom: 4px;
}

#gtm-ecommerce-woo-event-inspector .event-properties {
    margin-left: 16px;
    color: #5f6368;
}

#gtm-ecommerce-woo-event-inspector .clear-history {
    cursor: pointer;
    color: #ffffff;
    border: 1px solid #fff;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    background: none;
}

#gtm-ecommerce-woo-event-inspector .toggle-size {
    cursor: pointer;
    background: none;
    border: none;
    color: #ffffff;
    font-size: 20px;
    padding: 10px;
}

#gtm-ecommerce-woo-event-inspector pre {
    white-space: pre-wrap;
    word-wrap: break-word;
    background-color: #f8f9fa;
    border: 1px solid #dadce0;
    border-radius: 4px;
    padding: 8px;
    color: #202124;
}

#gtm-ecommerce-woo-event-inspector code {
    background: none;
}

#gtm-ecommerce-woo-event-inspector h3 {
    color: #202124;
    font-weight: 500;
    margin-top: 16px;
    margin-bottom: 8px;
}

#gtm-ecommerce-woo-event-inspector ul {
    margin: 0;
    padding: 0;
}

.no-data-message {
  padding: 16px;
  color: #5f6368;
  text-align: center;
  font-style: italic;
}

.check-icon {
  color: #4CAF50;
  margin-right: 8px;
}

.consent-section {
  margin-bottom: 20px;
  padding: 16px;
}

.consent-section h3 {
  margin-top: 0;
  margin-bottom: 12px;
  color: #202124;
  font-size: 16px;
  font-weight: 500;
}

.consent-section pre {
  margin: 0;
}
</style> 