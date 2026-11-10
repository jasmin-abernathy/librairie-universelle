<section class="paper-spread-editor" id="paper-spread-editor" aria-labelledby="spread-heading">
  <h3 id="spread-heading">Calculer et composer une couverture complète à plat</h3>
  <p>Quatrième, tranche et première de couverture sont assemblées dans cet ordre. Le papier est choisi avant la pagination ; son épaisseur réelle ou la cote de dos communiquée par l’imprimeur est indispensable à l’export.</p>
  <div class="paper-wizard-price-inputs">
    <label>Épaisseur réelle d’une feuille (mm)
      <input id="spread-leaf-mm" type="number" min="0.04" max="0.4" step="0.001" inputmode="decimal" placeholder="Donnée fournie par l’imprimeur">
    </label>
    <label>Ou largeur du dos confirmée (mm)
      <input id="spread-spine-mm" type="number" min="0.5" max="100" step="0.01" inputmode="decimal" placeholder="Cote de votre gabarit officiel">
    </label>
    <label>Fond perdu par bord (mm)
      <input id="spread-bleed-mm" type="number" min="0" max="15" step="0.5" value="8" inputmode="decimal">
    </label>
    <label>Couleur de couverture
      <input id="spread-bg" type="color" value="#275842">
    </label>
  </div>
  <p class="paper-wizard-help">Le fond perdu de 8 mm correspond aux recommandations générales de Bookelis pour la couverture. Le gabarit généré par Bookelis pour votre ouvrage reste la référence. La reliure rigide exige d’autres dimensions (charnières, rabats) et ne peut pas être calculée par cet outil.</p>
  <div class="paper-wizard-price-inputs">
    <label>Titre du livre
      <input id="spread-title" maxlength="75" autocomplete="off" placeholder="Titre de couverture">
    </label>
    <label>Nom de l’auteur
      <input id="spread-author" maxlength="70" autocomplete="off" placeholder="Nom sur la première et le dos">
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
  <p class="field-help">Les images restent sur votre appareil. Si vous avez saisi un ISBN valide dans l’assistant, son code-barres sera repris uniquement si vous avez choisi la génération automatique. Un code-barres déjà fourni ne sera pas écrasé.</p>
  <p class="paper-spread-dimensions" id="spread-dimensions" aria-live="polite">Dimensions en attente du nombre de pages et du papier.</p>
  <div class="paper-spread-preview" id="spread-preview" aria-label="Aperçu technique : quatrième, tranche, première de couverture"></div>
  <button class="paper-wizard-primary" id="spread-download" type="button" disabled>Télécharger le PDF de couverture complète (300 ppp)</button>
  <p class="paper-wizard-help" id="spread-status" role="status" aria-live="polite">Indiquez l’épaisseur du papier ou la cote de dos confirmée par Bookelis.</p>
  <p class="paper-wizard-help"><strong>Important :</strong> ce PDF est un document de travail à contrôler sur le gabarit officiel. La conformité prépresse, notamment CMJN, traits de coupe, fonds perdus et code-barres, doit être validée par l’imprimeur avant commande.</p>
</section>
