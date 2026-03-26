<style>
    .content-wrapper {
        margin: 0;
        padding: 0;
    }
</style>

<div class="d-flex justify-content-center align-items-center h-100">
    <div class="col-12 text-center">
        <h1>403</h1>
        <h2>Non autorizzato</h2>
        <p>Non hai i permessi per accedere a questa pagina</p>
        <a href="#" onclick="sendBack()" class="btn btn-outline-secondary mt-4">
            Torna indietro
        </a>
    </div>
</div>

<script>
    function sendBack() {
        window.history.back();
    }
</script>