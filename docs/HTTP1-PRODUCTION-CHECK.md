# Vérification de production sur GitHub Actions : compatibilité HTTPS

Le contrôle `scripts/verify-production.sh` force désormais **HTTP/1.1** pour les GET et le HEAD sur `librairie.lepotager.org`. GitHub Actions recevait des erreurs `curl: (92) HTTP/2 PROTOCOL_ERROR` avant de pouvoir récupérer le SHA du site. Cet échec **ne signifie pas** que le site était sur une mauvaise révision : aucune comparaison n'a eu lieu.

Les tentatives sont limitées à 6 avec un intervalle de 18 secondes ; des problèmes permanents d'accès sont signalés comme échecs explicites, sans multiplier indéfiniment les contrôles. Le SHA, les pages et les en-têtes sont toujours vérifiés sans passer par Make.

Si même HTTP/1.1 reste inaccessible depuis les runners GitHub, investiguer un filtrage réseau o2switch (WAF/ModSecurity/proxy) et ne pas contourner la sécurité en acceptant un HTTP 200 sans concordance de SHA.
