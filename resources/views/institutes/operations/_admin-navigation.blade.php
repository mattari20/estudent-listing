<nav class="institute-card" aria-label="Institute admin navigation">
    <h2>Admin Navigation</h2>
    <p><a href="{{ route('institutes.operations.queue') }}">Operations Hub</a></p>
    <p><a href="{{ route('institutes.operations.import.create') }}">Import Institutions</a></p>
    <p><a href="{{ route('institutes.operations.enrichment-import.create') }}">Import Campus / Program Enrichment</a></p>
    <p><a href="{{ route('institutes.operations.geography-import.create') }}">Import Geography</a></p>
    <p><a href="{{ route('institutes.operations.data-readiness') }}">Data Readiness</a> · <a href="{{ route('institutes.operations.integrity') }}">Geography Integrity</a></p>
    <p><a href="{{ route('institutes.operations.readiness-dashboard') }}">Readiness Dashboard</a> · <a href="{{ route('institutes.operations.production-signoff') }}">Production Sign-off</a></p>
    <p><a href="{{ route('institutes.operations.monitoring.dashboard') }}">Monitoring</a> · <a href="{{ route('institutes.operations.monitoring.history') }}">Monitoring History</a></p>
    @if(auth()->user()?->isAdmin())
        <p><a href="{{ route('institutes.operations.users.index') }}">User Management</a></p>
    @endif
</nav>
