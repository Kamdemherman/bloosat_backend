<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
  body  { margin:0; padding:0; background:#f1f5f9; font-family:Arial,sans-serif; color:#334155; }
  .wrap { max-width:600px; margin:32px auto; background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,.08); }
  .hdr  { background:#1d4ed8; padding:28px 36px; }
  .hdr h1 { margin:0; font-size:22px; color:#fff; }
  .hdr p  { margin:4px 0 0; font-size:13px; color:#bfdbfe; }
  .body { padding:32px 36px; }
  .alert-warn  { background:#fffbeb; border-left:4px solid #f59e0b; border-radius:0 6px 6px 0; padding:14px 18px; margin:20px 0; font-size:14px; color:#92400e; }
  .alert-late  { background:#fef2f2; border-left:4px solid #ef4444; border-radius:0 6px 6px 0; padding:14px 18px; margin:20px 0; font-size:14px; color:#991b1b; }
  .amount      { font-size:32px; font-weight:700; color:#1d4ed8; margin:16px 0; }
  .row  { display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid #f1f5f9; font-size:14px; }
  .lbl  { color:#64748b; }
  .val  { font-weight:600; color:#1e293b; }
  .foot { background:#f8fafc; padding:18px 36px; text-align:center; font-size:12px; color:#94a3b8; border-top:1px solid #e2e8f0; }
</style>
</head>
<body>
<div class="wrap">
  <div class="hdr">
    <h1>BLOOSAT</h1>
    <p>Business Support System</p>
  </div>
  <div class="body">
    <p style="font-size:15px">Bonjour <strong>{{ $client->display_name }}</strong>,</p>

    @if($daysOffset > 0)
      <div class="alert-warn">
        ⚠️ Votre redevance mensuelle arrive à échéance dans <strong>{{ $daysOffset }} jour(s)</strong>.
        Merci d'effectuer votre paiement avant la date limite pour éviter toute interruption de service.
      </div>
    @else
      <div class="alert-late">
        🔴 Votre redevance mensuelle accuse un retard de <strong>{{ abs($daysOffset) }} jour(s)</strong>.
        Veuillez régulariser votre situation dans les meilleurs délais.
      </div>
    @endif

    <div class="amount">{{ $amount }}</div>

    <div class="row"><span class="lbl">Période</span><span class="val">{{ $redevance->period_start->format('d/m/Y') }} — {{ $redevance->period_end->format('d/m/Y') }}</span></div>
    <div class="row"><span class="lbl">Date d'échéance</span><span class="val">{{ $dueDate }}</span></div>
    <div class="row"><span class="lbl">Référence facture</span><span class="val">{{ $redevance->invoice->number }}</span></div>

    <p style="margin-top:24px;font-size:14px;color:#64748b;line-height:1.6">
      Pour effectuer votre paiement, veuillez contacter votre commercial ou le service financier de BLOOSAT.<br>
      Joignez votre preuve de paiement à votre retour.
    </p>

    <p style="font-size:14px;margin-top:24px">
      Cordialement,<br>
      <strong>L'équipe BLOOSAT</strong>
    </p>
  </div>
  <div class="foot">
    © {{ date('Y') }} BLOOSAT — Email automatique, merci de ne pas répondre directement.
  </div>
</div>
</body>
</html>
