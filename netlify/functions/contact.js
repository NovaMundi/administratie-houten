const reply = (statusCode, body) => ({
  statusCode,
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify(body),
});

exports.handler = async (event) => {
  if (event.httpMethod !== 'POST') {
    return reply(405, { ok: false, error: 'Method not allowed' });
  }

  let data;
  try {
    data = JSON.parse(event.body || '{}');
  } catch {
    return reply(400, { ok: false, error: 'Invalid JSON' });
  }

  const naam    = (data['Voornaam'] ?? data['Naam'] ?? '').trim();
  const achter  = (data['Achternaam'] ?? '').trim();
  const email   = (data['E-mailadres'] ?? '').trim();
  const tel     = (data['Telefoonnummer'] ?? '').trim();
  const dienst  = (data['Wat zoekt u?'] ?? data['Wat kan ik voor u betekenen?'] ?? '').trim();
  const bericht = (data['Bericht'] ?? '').trim();

  if (!naam || !email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    return reply(422, { ok: false, error: 'Naam en geldig e-mailadres zijn verplicht' });
  }

  const lines = [
    `Naam:      ${[naam, achter].filter(Boolean).join(' ')}`,
    `E-mail:    ${email}`,
    tel     ? `Telefoon:  ${tel}`    : null,
    dienst  ? `Onderwerp: ${dienst}` : null,
    bericht ? `\nBericht:\n${bericht}` : null,
  ].filter(Boolean).join('\n');

  const text = [
    'Nieuw contactverzoek van administratiehouten.nl',
    '='.repeat(48),
    '',
    lines,
    '',
    '='.repeat(48),
    `Verzonden op: ${new Date().toLocaleString('nl-NL', { timeZone: 'Europe/Amsterdam' })}`,
  ].join('\n');

  const res = await fetch('https://api.resend.com/emails', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${process.env.RESEND_API_KEY}`,
    },
    body: JSON.stringify({
      from: 'Administratie Houten <noreply@administratiehouten.nl>',
      to: ['info@administratiehouten.nl'],
      reply_to: email,
      subject: 'Nieuw contactverzoek via administratiehouten.nl',
      text,
    }),
  });

  if (res.ok) {
    return reply(200, { ok: true });
  }

  const err = await res.json().catch(() => ({}));
  console.error('Resend error', res.status, err);
  return reply(500, { ok: false, error: 'Kon e-mail niet verzenden' });
};
