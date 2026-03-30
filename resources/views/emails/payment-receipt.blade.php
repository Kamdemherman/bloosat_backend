<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
  body  { margin:0; padding:0; background:#f1f5f9; font-family:Arial,sans-serif; color:#334155; }
  .wrap { max-width:600px; margin:32px auto; background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,.08); }
  .hdr  { background:#059669; padding:28px 36px; }
  .hdr h1 { margin:0; font-size:20px; color:#fff; }
  .hdr p  { margin:4px 0 0; font-size:13px; color:#a7f3d0; }
  .body { padding:32px 36px; }
  .check-box { text-align:center; margin:24px 0; }
  .check-icon { font-size:52px; }
  .amount { font-size:34px; font-weight:700; color:#059669; text-align:center; margin:8px 0 28px; }
  .status-badge { display:inline-block; padding:6px 14px; border-radius:20px; font-size:12px; font-weight:600; text-align:center; margin-top:12px; }
  .status-complete { background:#dcfce7; color:#166534; }
  .status-incomplete { background:#fee2e2; color:#991b1b; }
  .row  { display:flex; justify-content:space-between; padding:11px 0; border-bottom:1px solid #f1f5f9; font-size:14px; }
  .lbl  { color:#64748b; }
  .val  { font-weight:600; color:#1e293b; }
  .ref  { margin-top:28px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:14px 18px; font-size:13px; color:#166534; text-align:center; }
  .foot { background:#f8fafc; padding:18px 36px; text-align:center; font-size:12px; color:#94a3b8; border-top:1px solid #e2e8f0; }
</style>
</head>
<body>
<div class="wrap">
  <div class="hdr">
    <h1>BLOOSAT — Reçu de paiement</h1>
    <p>Business Support System</p>
  </div>
  <div class="body">
    <div class="check-box"><div class="check-icon">✅</div></div>
    <div class="amount">{{ $amount }}</div>

    <p style="font-size:15px">Cher(e) <strong>{{ $client->display_name }}</strong>,</p>
    <p style="font-size:14px;color:#64748b;line-height:1.6">
      Votre paiement a bien été reçu et enregistré dans notre système. Merci pour votre règlement.
    </p>

    <div class="row"><span class="lbl">Référence encaissement</span><span class="val">{{ $encaissement->reference }}</span></div>
    <div class="row"><span class="lbl">Facture réglée</span><span class="val">{{ $encaissement->invoice->number }}</span></div>
    <div class="row"><span class="lbl">Montant réglé</span><span class="val">{{ $amount }}</span></div>
    <div class="row"><span class="lbl">Méthode de paiement</span><span class="val">{{ ucfirst(str_replace('_', ' ', $encaissement->payment_method)) }}</span></div>
    <div class="row"><span class="lbl">Date de paiement</span><span class="val">{{ $date }}</span></div>
    <div class="row"><span class="lbl">Enregistré par</span><span class="val">{{ $encaissement->creator->name }}</span></div>

    @if($status === 'INCOMPLET')
      <div style="margin-top:16px;padding:12px 16px;background:#fef2f2;border:1px solid #fecaca;border-radius:6px;font-size:13px">
        <strong style="color:#991b1b">⚠️ Paiement incomplet</strong><br>
        <span style="color:#7f1d1d">Montant facture : {{ $invoice->total_formatted }} | Montant reçu : {{ $amount }}</span><br>
        <span style="color:#7f1d1d">Solde restant dû : <strong>{{ $remaining }}</strong></span>
      </div>
    @endif

    <div style="text-align:center;margin-top:12px">
      <span class="status-badge {{ $status === 'COMPLET' ? 'status-complete' : 'status-incomplete' }}">
        {{ $status === 'COMPLET' ? '✓ PAIEMENT COMPLET' : '⚠ PAIEMENT INCOMPLET' }}
      </span>
    </div>

    <div class="ref">
      📋 Référence : <strong>{{ $encaissement->reference }}</strong><br>
      Conservez ce reçu pour vos archives comptables.
    </div>

    <p style="font-size:14px;margin-top:28px;color:#64748b;line-height:1.6">
      Votre service reste actif. Pour toute question, contactez votre commercial BLOOSAT.
    </p>

    <p style="font-size:14px;margin-top:16px">
      Cordialement,<br>
      <strong>L'équipe BLOOSAT</strong>
    </p>
  </div>
  <div class="foot">© {{ date('Y') }} BLOOSAT — Conservez ce reçu pour vos archives.</div>
</div>
</body>
</html>
