<section class="section ai-section" id="ia">
    <div class="container ai-grid">
        <div class="reveal">
            <span class="eyebrow">Simulacao visual com IA</span>
            <h2 class="section-title">Uma pre-analise visual para orientar o melhor caminho antes da visita.</h2>
            <p class="section-copy">Envie uma imagem da area externa para apoiar a leitura do material, do nivel de lodo ou musgo e das condicoes aparentes do ambiente. A simulacao ajuda a alinhar expectativa visual com criterio tecnico.</p>

            <div class="ai-notes">
                <span class="tag tag--gold">Leitura por imagem</span>
                <span class="tag">Apoio ao orcamento</span>
                <span class="tag">Criterio tecnico</span>
            </div>
        </div>

        <form class="card upload-panel reveal" action="../../admin/api/upload.php" method="post" enctype="multipart/form-data" data-upload-form>
            <?= csrf_field(); ?>
            <input type="hidden" name="lead_id" data-ai-lead-id>
            <input class="sr-only" id="environment-image" name="environment_image" type="file" accept="image/png,image/jpeg,image/webp" data-upload-input>

            <label class="upload-dropzone" for="environment-image" data-upload-dropzone>
                <span class="upload-dropzone__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M4 16.5V19a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2.5"/><path d="M12 3v13"/><path d="m7 8 5-5 5 5"/></svg>
                </span>
                <strong>Envie uma foto da area externa</strong>
                <small>Imagem em PNG, JPG ou WEBP ate 10MB</small>
            </label>

            <div class="upload-preview" data-upload-preview hidden>
                <img src="" alt="Preview da imagem enviada" data-upload-image>
                <div class="upload-preview__meta">
                    <strong data-upload-name>Imagem selecionada</strong>
                    <span data-upload-size>Preparando preview</span>
                </div>
            </div>

            <div class="upload-loading" data-upload-loading hidden>
                <span></span>
                <p>Gerando uma simulacao visual com acabamento tecnico...</p>
            </div>

            <div class="simulation-result" data-simulation-result hidden>
                <div>
                    <span class="eyebrow">Resultado IA</span>
                    <h3>Previa de revitalizacao</h3>
                </div>
                <img src="" alt="Simulacao IA de revitalizacao" data-simulation-image>
                <p>Imagem gerada para orientar percepcao visual. A avaliacao tecnica final considera material, acesso, seguranca e condicoes reais do ambiente.</p>
            </div>

            <button class="btn btn--primary" type="button" data-upload-button>Enviar imagem para analise</button>
            <p class="upload-helper">Upload seguro para apoiar diagnostico, armazenamento controlado e resposta consultiva.</p>
        </form>
    </div>
</section>
