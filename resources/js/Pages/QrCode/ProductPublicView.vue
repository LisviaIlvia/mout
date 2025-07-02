<template>
  <div class="product-public-view">
    <!-- Header con logo azienda -->
    <div class="header-section">
      <div class="company-info">
        <img src="/images/logo.png" alt="Logo Azienda" class="company-logo" />
        <div class="company-details">
          <h1 class="company-name">MOUT CRM</h1>
          <p class="company-subtitle">Sistema di Gestione Prodotti</p>
        </div>
      </div>
      <div class="qr-info">
        <div class="qr-badge">
          <v-icon icon="mdi-qrcode" size="24" color="primary"></v-icon>
          <span>QR Code Prodotto</span>
        </div>
      </div>
    </div>

    <!-- Informazioni Prodotto -->
    <div class="product-section">
      <div class="product-header">
        <h2 class="product-title">{{ product.nome }}</h2>
        <div class="product-code">
          <span class="code-label">Codice:</span>
          <span class="code-value">{{ product.codice }}</span>
        </div>
      </div>

      <!-- Dettagli Prodotto -->
      <div class="product-details">
        <div class="detail-row">
          <div class="detail-item">
            <span class="detail-label">Descrizione:</span>
            <span class="detail-value">{{ product.descrizione || 'Nessuna descrizione' }}</span>
          </div>
        </div>

        <div class="detail-row">
          <div class="detail-item">
            <span class="detail-label">Unità di Misura:</span>
            <span class="detail-value">{{ product.unita_misura }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Prezzo:</span>
            <span class="detail-value price">€ {{ formatPrice(product.prezzo) }}</span>
          </div>
        </div>

        <div class="detail-row">
          <div class="detail-item">
            <span class="detail-label">Aliquota IVA:</span>
            <span class="detail-value">{{ product.aliquota_iva?.aliquota || 0 }}%</span>
          </div>
          <div class="detail-item" v-if="product.giacenza_iniziale !== null">
            <span class="detail-label">Giacenza Iniziale:</span>
            <span class="detail-value">{{ product.giacenza_iniziale }}</span>
          </div>
        </div>

        <!-- Categorie -->
        <div class="detail-row" v-if="product.categorie && product.categorie.length > 0">
          <div class="detail-item full-width">
            <span class="detail-label">Categorie:</span>
            <div class="categories-list">
              <v-chip
                v-for="categoria in product.categorie"
                :key="categoria.id"
                size="small"
                color="primary"
                variant="outlined"
                class="category-chip"
              >
                {{ categoria.nome }}
              </v-chip>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Documenti Associati -->
    <div class="documents-section" v-if="documents && Object.keys(documents).length > 0">
      <h3 class="section-title">Documenti Associati</h3>
      
      <div class="documents-list">
        <div v-for="(docs, type) in documents" :key="type" class="document-group">
          <h4 class="document-type-title">{{ getDocumentTypeLabel(type) }}</h4>
          
          <div class="document-items">
            <div v-for="doc in docs" :key="doc.id" class="document-item">
              <div class="document-info">
                <span class="document-number">{{ doc.numero }}</span>
                <span class="document-date">{{ formatDate(doc.data) }}</span>
                <span class="document-entity" v-if="doc.entity">{{ doc.entity.nome }}</span>
                <span class="document-status" :class="'status-' + doc.stato">
                  {{ doc.stato }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="footer-section">
      <div class="footer-info">
        <p class="footer-text">
          Questo QR code è stato generato automaticamente dal sistema MOUT CRM
        </p>
        <p class="footer-date">
          Generato il: {{ formatDate(new Date()) }}
        </p>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ProductPublicView',
  props: {
    product: {
      type: Object,
      required: true
    },
    documents: {
      type: Object,
      default: () => ({})
    },
    title: {
      type: String,
      default: 'Prodotto'
    }
  },
  methods: {
    formatPrice(price) {
      if (!price) return '0,00';
      return parseFloat(price).toFixed(2).replace('.', ',');
    },
    formatDate(date) {
      if (!date) return '';
      const d = new Date(date);
      return d.toLocaleDateString('it-IT', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      });
    },
    getDocumentTypeLabel(type) {
      const labels = {
        'ordini-vendita': 'Ordini di Vendita',
        'ordini-acquisto': 'Ordini di Acquisto',
        'fatture-vendita': 'Fatture di Vendita',
        'fatture-acquisto': 'Fatture di Acquisto',
        'ddt-uscita': 'DDT di Uscita',
        'ddt-entrata': 'DDT di Entrata',
        'note-credito-attive': 'Note di Credito Attive',
        'note-credito-passive': 'Note di Credito Passive'
      };
      return labels[type] || type;
    }
  }
}
</script>

<style scoped>
.product-public-view {
  max-width: 800px;
  margin: 0 auto;
  padding: 20px;
  font-family: 'Roboto', sans-serif;
  background: #f5f5f5;
  min-height: 100vh;
}

.header-section {
  background: white;
  padding: 20px;
  border-radius: 8px;
  margin-bottom: 20px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.company-info {
  display: flex;
  align-items: center;
  gap: 15px;
}

.company-logo {
  width: 60px;
  height: 60px;
  object-fit: contain;
}

.company-name {
  font-size: 24px;
  font-weight: bold;
  color: #1976d2;
  margin: 0;
}

.company-subtitle {
  color: #666;
  margin: 0;
  font-size: 14px;
}

.qr-info {
  display: flex;
  align-items: center;
}

.qr-badge {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  background: #e3f2fd;
  border-radius: 20px;
  color: #1976d2;
  font-weight: 500;
}

.product-section {
  background: white;
  padding: 20px;
  border-radius: 8px;
  margin-bottom: 20px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.product-header {
  border-bottom: 2px solid #e0e0e0;
  padding-bottom: 15px;
  margin-bottom: 20px;
}

.product-title {
  font-size: 28px;
  font-weight: bold;
  color: #333;
  margin: 0 0 10px 0;
}

.product-code {
  display: flex;
  align-items: center;
  gap: 8px;
}

.code-label {
  font-weight: 500;
  color: #666;
}

.code-value {
  font-weight: bold;
  color: #1976d2;
  font-size: 16px;
}

.product-details {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.detail-row {
  display: flex;
  gap: 30px;
  flex-wrap: wrap;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 5px;
  min-width: 200px;
}

.detail-item.full-width {
  width: 100%;
}

.detail-label {
  font-weight: 500;
  color: #666;
  font-size: 14px;
}

.detail-value {
  font-size: 16px;
  color: #333;
}

.detail-value.price {
  font-weight: bold;
  color: #2e7d32;
  font-size: 18px;
}

.categories-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 5px;
}

.category-chip {
  font-size: 12px;
}

.documents-section {
  background: white;
  padding: 20px;
  border-radius: 8px;
  margin-bottom: 20px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.section-title {
  font-size: 20px;
  font-weight: bold;
  color: #333;
  margin: 0 0 15px 0;
  border-bottom: 1px solid #e0e0e0;
  padding-bottom: 10px;
}

.document-group {
  margin-bottom: 20px;
}

.document-type-title {
  font-size: 16px;
  font-weight: 600;
  color: #1976d2;
  margin: 0 0 10px 0;
}

.document-items {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.document-item {
  background: #f8f9fa;
  padding: 12px;
  border-radius: 6px;
  border-left: 4px solid #1976d2;
}

.document-info {
  display: flex;
  align-items: center;
  gap: 15px;
  flex-wrap: wrap;
}

.document-number {
  font-weight: bold;
  color: #333;
}

.document-date {
  color: #666;
  font-size: 14px;
}

.document-entity {
  color: #1976d2;
  font-weight: 500;
}

.document-status {
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
  text-transform: uppercase;
}

.status-bozza {
  background: #fff3e0;
  color: #f57c00;
}

.status-confermato {
  background: #e8f5e8;
  color: #2e7d32;
}

.status-evaso {
  background: #e3f2fd;
  color: #1976d2;
}

.status-annullato {
  background: #ffebee;
  color: #c62828;
}

.footer-section {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  text-align: center;
}

.footer-text {
  color: #666;
  margin: 0 0 5px 0;
  font-size: 14px;
}

.footer-date {
  color: #999;
  margin: 0;
  font-size: 12px;
}

@media (max-width: 768px) {
  .product-public-view {
    padding: 10px;
  }
  
  .header-section {
    flex-direction: column;
    gap: 15px;
    text-align: center;
  }
  
  .detail-row {
    flex-direction: column;
    gap: 15px;
  }
  
  .document-info {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
}
</style> 