/* Entretien privé : brouillon distant, sans stockage local de réponses sensibles. */
(() => {
  'use strict';
  const form = document.querySelector('#interview-answer-form');
  if (!form) return;
  const answer = form.querySelector('#answer');
  const status = form.querySelector('#interview-save-status');
  const fingerprint = form.querySelector('#interview-answer-fingerprint');
  const csrf = form.querySelector('input[name="csrf"]');
  const cursor = form.querySelector('input[name="cursor"]');
  if (!answer || !status || !fingerprint || !csrf || !cursor) return;

  const initialUnsaved = answer.dataset.unsaved === 'true';
  let savedText = initialUnsaved ? null : answer.value;
  let timer = null;
  let pending = null;
  let submitting = false;
  let readyToSubmit = false;
  let failed = false;

  function announce(message, error = false) {
    status.textContent = message;
    status.dataset.state = error ? 'error' : 'normal';
  }

  function payload(snapshot = answer.value) {
    const fields = new URLSearchParams();
    fields.set('action', 'autosave');
    fields.set('csrf', csrf.value);
    fields.set('cursor', cursor.value);
    fields.set('fingerprint', fingerprint.value);
    fields.set('answer', snapshot);
    return fields;
  }

  function schedule(delay = 1300) {
    clearTimeout(timer);
    if (submitting) return;
    announce('Modifications en attente d’enregistrement…');
    timer = window.setTimeout(() => { void saveNow(); }, delay);
  }

  async function saveNow() {
    clearTimeout(timer);
    if (submitting) return;
    if (pending) {
      await pending;
      if (!failed && answer.value !== savedText) return saveNow();
      return;
    }
    if (savedText === answer.value) return;

    const snapshot = answer.value;
    announce('Enregistrement du brouillon…');
    pending = (async () => {
      try {
        const response = await fetch(window.location.href, {
          method: 'POST',
          credentials: 'same-origin',
          cache: 'no-store',
          headers: { Accept: 'application/json' },
          body: payload(snapshot)
        });
        let result;
        try {
          result = await response.json();
        } catch (_) {
          throw new Error('Réponse du serveur indisponible.');
        }
        if (!response.ok || !result || result.ok !== true
            || !/^[a-f0-9]{64}$/.test(String(result.fingerprint || ''))) {
          throw new Error(result && result.message ? result.message : 'Enregistrement non confirmé.');
        }
        fingerprint.value = result.fingerprint;
        savedText = snapshot;
        failed = false;
        if (answer.value === snapshot) {
          const stamp = new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
          announce('Brouillon enregistré à ' + stamp + '. Vous pouvez reprendre plus tard.');
        } else {
          schedule(500);
        }
      } catch (error) {
        failed = true;
        announce('Enregistrement non confirmé : ' + (error.message || 'vérifiez votre connexion') + ' Ne fermez pas cette page sans vérifier la sauvegarde.', true);
      } finally {
        pending = null;
      }
    })();
    await pending;
  }

  answer.addEventListener('input', () => {
    failed = false;
    schedule();
  });
  answer.addEventListener('blur', () => {
    if (answer.value !== savedText && !submitting) {
      void saveNow();
    }
  });
  window.addEventListener('online', () => {
    if (!submitting && answer.value !== savedText) {
      failed = false;
      schedule(100);
    }
  });

  // A native form POST remains the reliable fallback when JavaScript is disabled.
  // Wait for an in-flight autosave before navigation to keep the fingerprint in sync.
  form.addEventListener('submit', (event) => {
    if (readyToSubmit) return;
    event.preventDefault();
    if (submitting) return;
    submitting = true;
    clearTimeout(timer);
    const submitter = event.submitter || form.querySelector('button[value="next"]');
    announce('Enregistrement final de cette étape…');
    const finish = async () => {
      if (pending) await pending;
      readyToSubmit = true;
      form.requestSubmit(submitter);
    };
    void finish();
  });

  // Best effort on browser close. Explicit "Enregistrer et quitter" provides
  // confirmation and must be preferred to relying on this browser event.
  window.addEventListener('pagehide', () => {
    if (submitting || pending || savedText === answer.value) return;
    clearTimeout(timer);
    if (navigator.sendBeacon) navigator.sendBeacon(window.location.href, payload());
  });

  announce(initialUnsaved
    ? 'Cette réponse n’a pas encore été enregistrée : la sauvegarde automatique va réessayer.'
    : 'Sauvegarde automatique activée. Vos modifications sont enregistrées après une courte pause.');
  if (initialUnsaved) schedule(200);
})();
