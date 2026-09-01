# Corpus MIME commun des POC milter

Ces messages proviennent du POC Amavis du commit
`5516586711cd3c380f0fea0a987c6c4c9c3c740b`. Ils sont placés hors de
l'implémentation Amavis afin que chaque candidat A1 lise exactement les mêmes
octets.

## Cas initiaux

| Fichier | Invariant attendu |
|---|---|
| `no-url.eml` | Aucune transformation et aucune commande de remplacement du corps |
| `plain-quoted-printable.eml` | Deux URL réécrites ; charset et ponctuation préservés |
| `html-base64.eml` | Un `href` HTTP réécrit ; `mailto` et texte visible inchangés |
| `multipart-mixed.eml` | Alternatives texte et HTML réécrites ; pièce jointe inchangée |
| `incoming-dkim.eml` | Résultat DKIM entrant conservé ; invalidation de la signature attendue après modification |

La transformation de démonstration doit être idempotente : une seconde passe
ne remplace pas le corps et ne modifie aucun octet. Les assertions doivent
porter sur le contenu MIME décodé et sur les invariants ci-dessus, pas sur les
choix de sérialisation propres à une bibliothèque.

## Cas à ajouter

Le corpus initial ne couvre pas encore tous les critères A1. Il faut ajouter
avant la décision :

- `message/rfc822` imbriqué ;
- texte 8-bit et Windows-1252 ;
- HTML mal formé ;
- parties signées S/MIME et PGP ;
- pièce jointe binaire avec hash de référence ;
- messages synthétiques d'environ 100 Ko, 5 Mo et 40 Mo.

Les gros messages doivent être générés de manière déterministe par le banc de
charge plutôt que stockés dans Git.
