<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
  body  { margin:0; padding:0; background:#f1f5f9; font-family:Arial,sans-serif; color:#334155; }
  .wrap { max-width:600px; margin:32px auto; background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,.08); }
  .hdr  { background:#dc2626; padding:28px 36px; }
  .hdr h1 { margin:0; font-size:20px; color:#fff; }
  .hdr p  { margin:6px 0 0; font-size:13px; color:#fecaca; }
  .body { padding:32px 36px; }
  .badge { display:inline-block; background:#fef2f2; color:#dc2626; border:1px solid #fecaca; border-radius:4px; padding:5px 14px; font-size:12px; font-weight:700; letter-spacing:.5px; margin-bottom:20px; }
  .row  { display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid #f1f5f9; font-size:14px; }
  .lbl  { color:#64748b; }
  .val  { font-weight:600; color:#1e293b; }
  .foot { background:#f8fafc; padding:18px 36px; text-align:center; font-size:12px; color:#94a3b8; border-top:1px solid #e2e8f0; }
  .note { margin-top:24px; padding:14px 18px; background:#fff7ed; border-left:4px solid #f97316; border-radius:0 6px 6px 0; font-size:14px; color:#9a3412; }
</style>
</head>
<body>
<div class="wrap">
  <div class="hdr">
    <h1>🔴 Notification de suspension automatique</h1>
    <p>BLOOSAT Business Support System</p>
  </div>
  <div class="body">
    <div class="badge">SUSPENSION AUTOMATIQUE — NON-PAIEMENT</div>

    <p style="font-size:15px">Le client suivant a été <strong>suspendu automatiquement</strong> par le système pour défaut de paiement de sa redevance mensuelle :</p>

    <div class="row"><span class="lbl">Client</span><span class="val">{{ $client->display_name }}</span></div>
    <div class="row"><span class="lbl">Type</span><span class="val">{{ $client->type === 'grand_compte' ? 'Grand Compte' : 'Ordinaire' }}</span></div>
    <div class="row"><span class="lbl">Email</span><span class="val">{{ $client->email ?? 'N/A' }}</span></div>
    <div class="row"><span class="lbl">Téléphone</span><span class="val">{{ $client->telephone ?? 'N/A' }}</span></div>
    <div class="row"><span class="lbl">Ville</span><span class="val">{{ $client->ville ?? 'N/A' }}</span></div>
    <div class="row"><span class="lbl">Date de suspension</span><span class="val">{{ $date }}</span></div>

    <div class="note">
      ⚠️ Veuillez prendre les mesures nécessaires pour le recouvrement des fonds.
      Le service sera rétabli dès réception et enregistrement du paiement dans le BSS.
    </div>
  </div>
  <div class="foot">© {{ date('Y') }} BLOOSAT BSS — Notification automatique du système</div>
</div>
</body>
</html>
