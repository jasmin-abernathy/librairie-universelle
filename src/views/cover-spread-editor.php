<section class="paper-spread-editor" id="paper-spread-editor" aria-labelledby="spread-heading">
  <h3 id="spread-heading">Couverture Bookelis — à partir du gabarit officiel</h3>
  <p>Après la création du <strong>PDF intérieur</strong>, reportez ces informations dans le générateur de Bookelis, téléchargez son <strong>gabarit PDF</strong>, puis importez-le ici. La largeur du dos sera calculée depuis ce fichier, sans estimation à partir du grammage.</p>

  <section class="bookelis-guide" aria-labelledby="bookelis-guide-title">
    <h4 id="bookelis-guide-title">Informations à reporter sur Bookelis</h4>
    <dl class="bookelis-guide-grid">
      <div><dt>Nombre de pages</dt><dd id="bookelis-guide-pages">En attente du PDF intérieur</dd></div>
      <div><dt>Format</dt><dd id="bookelis-guide-format">—</dd></div>
      <div><dt>Couleur d’impression</dt><dd id="bookelis-guide-ink">À sélectionner</dd></div>
      <div><dt>Papier intérieur</dt><dd id="bookelis-guide-paper">—</dd></div>
      <div><dt>Type de couverture</dt><dd id="bookelis-guide-cover">—</dd></div>
      <div><dt>Format du gabarit</dt><dd>PDF</dd></div>
    </dl>
    <div class="paper-wizard-price-inputs">
      <label>Impression intérieure à reporter
        <select id="bookelis-ink">
          <option value="">À choisir selon le PDF intérieur</option>
          <option value="Noir et blanc">Noir et blanc</option>
          <option value="Couleur">Couleur</option>
        </select>
      </label>
      <label>Commercialisation sur Bookelis
        <select id="bookelis-commercialisation">
          <option value="">À choisir selon votre projet</option>
          <option value="Cercle privé">Cercle privé (pour soi, sans vente publique sur Bookelis)</option>
          <option value="Librairie Bookelis">Librairie Bookelis</option>
          <option value="Distribution Hachette">Distribution Hachette (papier offset 80 g requis)</option>
        </select>
      </label>
      <label>Pelliculage de la couverture
        <select id="bookelis-lamination">
          <option value="">À choisir sur Bookelis</option>
          <option value="Mat">Mat</option>
          <option value="Brillant">Brillant</option>
          <option value="Pas de pelliculage">Pas de pelliculage</option>
        </select>
      </label>
    </div>
    <p id="bookelis-guide-warning" class="paper-wizard-help" role="status">Les champs non déductibles du PDF restent à votre choix.</p>
    <div class="paper-step-actions">
      <button id="bookelis-guide-copy" class="button-link secondary" type="button">Copier le récapitulatif</button>
      <a class="button-link" href="https://livres.bookelis.com/generation-couverture" target="_blank" rel="noopener noreferrer">Ouvrir le générateur Bookelis ↗</a>
    </div>
    <p class="field-help">Dans Bookelis : saisissez ces valeurs, choisissez PDF, cliquez sur Valider puis Télécharger. Revenez importer le gabarit obtenu ici. Les sélections commerciales et le pelliculage ne sont jamais imposés par la Librairie.</p>
  </section>

  <div class="bookelis-template-import">
    <label for="bookelis-template-pdf"><strong>Importer le gabarit PDF officiel Bookelis (obligatoire)</strong></label>
    <input id="bookelis-template-pdf" type="file" accept=".pdf,application/pdf" aria-describedby="bookelis-template-help spread-status">
    <p id="bookelis-template-help" class="field-help">Une seule page PDF, générée pour la pagination, le format, le papier et la couverture choisis. Contrôle temporaire des dimensions, sans conservation du fichier sur notre serveur. Si ces paramètres changent, générez un nouveau gabarit.</p>
    <p id="bookelis-template-status" class="paper-wizard-help" role="status">Aucun gabarit importé : export de couverture verrouillé.</p>
    <a id="bookelis-template-preview" class="button-link secondary" target="_blank" rel="noopener noreferrer" hidden>Consulter mon gabarit importé</a>
  </div>

  <div class="paper-wizard-price-inputs">
    <label>Titre de couverture
      <input id="spread-title" maxlength="75" autocomplete="off" placeholder="Titre de couverture">
    </label>
    <label>Nom de l’auteur
      <input id="spread-author" maxlength="70" autocomplete="off" placeholder="Nom de l’auteur">
    </label>
    <label>Couleur principale
      <input id="spread-bg" type="color" value="#275842">
    </label>
  </div>
  <label>Texte de quatrième de couverture
    <textarea id="spread-description" maxlength="320" rows="4" placeholder="Présentation du livre"></textarea>
  </label>
  <div class="paper-wizard-price-inputs">
    <label>Illustration de première (facultative)
      <input id="spread-front-image" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
    </label>
    <label>Illustration de quatrième (facultative)
      <input id="spread-back-image" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
    </label>
  </div>
  <p class="field-help">Les illustrations restent sur votre appareil. Le code-barres est ajouté uniquement avec un ISBN valide et le mode de génération automatique.</p>
  <p id="spread-dimensions" class="paper-spread-dimensions" aria-live="polite">Les dimensions seront calculées à partir du PDF de gabarit importé.</p>
  <div id="spread-preview" class="paper-spread-preview" aria-label="Aperçu à plat : quatrième, tranche et première"></div>
  <button id="spread-download" class="paper-wizard-primary" type="button" disabled>Télécharger ma couverture complète (PDF de travail)</button>
  <p id="spread-status" class="paper-wizard-help" role="status" aria-live="polite">Importez le gabarit PDF avant la création de la couverture.</p>
  <p class="paper-wizard-help"><strong>Attention :</strong> ce PDF reste en RVB et n’est pas certifié prépresse. Bookelis exige notamment le CMJN, des traits de coupe et un contrôle du code-barres. Une vérification avec leurs consignes est obligatoire avant impression.</p>
</section>
