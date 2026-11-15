@extends('layouts.public', [
    'title' => 'Informativa privacy',
    'description' => 'Come Tatlas tratta i dati personali di chi cerca un tatuatore e dei tatuatori.',
    'url' => \App\Http\Controllers\Web\PublicPageController::base().'/privacy',
])

@section('content')
    <h1>Informativa privacy</h1>
    <p class="meta">Ultimo aggiornamento: {{ $updated }} · <strong>Bozza da far verificare a un consulente prima della pubblicazione sugli store.</strong></p>

    <h2>Titolare del trattamento</h2>
    <p>{{ $controller }} · contatto: <a href="mailto:{{ $email }}">{{ $email }}</a></p>

    <h2>Chi cerca un tatuatore</h2>
    <p>Per usare la mappa e cercare tatuatori non serve un account. Non usiamo strumenti di analisi o di tracciamento e non mostriamo pubblicità.</p>
    <p><strong>Posizione.</strong> Se la autorizzi, l'app legge la tua posizione solo mentre la usi. Le coordinate vengono inviate al nostro server soltanto per ordinare i risultati per distanza: non le associamo al tuo account e non le conserviamo. Puoi negare o revocare il permesso in qualsiasi momento dalle impostazioni del telefono.</p>

    <h2>Tatuatori e titolari di studi</h2>
    <ul>
        <li><strong>Account:</strong> nome, email e password (salvata solo in forma cifrata con hash). Servono a farti accedere e a contattarti per la verifica del profilo.</li>
        <li><strong>Profilo pubblico:</strong> nome d'arte, bio, stili, lingue, link a Instagram e al sito, studi in cui lavori, guest spot e foto del portfolio. Questi dati sono pubblici per scelta tua, visibili nell'app e sul web.</li>
        <li><strong>Foto del portfolio:</strong> prima del salvataggio vengono ridimensionate e ricodificate. I metadati (posizione GPS, modello del telefono, data di scatto) vengono eliminati.</li>
        <li><strong>Rivendicazioni:</strong> il messaggio e il link di verifica che invii servono solo a valutare la richiesta.</li>
    </ul>
    <p>Base giuridica: esecuzione del servizio che richiedi (art. 6.1.b GDPR) e consenso espresso alla registrazione.</p>

    <h2>Dati da OpenStreetMap</h2>
    <p>Nome, indirizzo e contatti degli studi provengono da <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> (licenza ODbL) e riguardano attività commerciali. Se sei il titolare puoi rivendicare lo studio e correggerne i dati, oppure chiederne la rimozione.</p>

    <h2>Conservazione</h2>
    <p>I dati dell'account e del profilo restano finché l'account è attivo. Quando elimini l'account (dall'app: Account → Elimina account) cancelliamo profilo, foto e collegamenti agli studi. Le richieste di rimozione vengono conservate per 24 mesi come prova della loro gestione.</p>

    <h2>Fornitori</h2>
    <p>Server e archivio delle immagini presso fornitori nell'Unione Europea o con garanzie adeguate. Mappe: OpenFreeMap (tile). Ricerca indirizzi: Photon (komoot), solo per gli indirizzi degli studi inseriti dai tatuatori. Nessun dato viene venduto o ceduto a terzi a fini commerciali.</p>

    <h2>I tuoi diritti</h2>
    <p>Puoi chiedere accesso, rettifica, cancellazione, limitazione e portabilità dei tuoi dati, e opporti al trattamento, scrivendo a <a href="mailto:{{ $email }}">{{ $email }}</a> o usando il <a href="{{ route('public.removal') }}">modulo di rimozione</a>. Hai anche diritto di presentare reclamo al Garante per la protezione dei dati personali (garanteprivacy.it).</p>

    <h2>Cookie</h2>
    <p>Il sito pubblico non usa cookie di profilazione. L'unico cookie tecnico è quello di sessione dell'area riservata agli amministratori.</p>
@endsection
