<section class="section quiz-section" id="quiz">
    <div class="container">
        <div class="quiz-shell card reveal" data-quiz>
            <div class="quiz-intro">
                <span class="eyebrow">Quiz inteligente</span>
                <h2>Diagnostico tecnico em poucos passos.</h2>
                <p>Responda uma etapa por vez para que a Galvao entenda o ambiente, as superficies, o nivel de manutencao e as imagens do local antes do atendimento.</p>
            </div>

            <form class="quiz-form" action="../../admin/ajax/quiz-submit.php" method="post" enctype="multipart/form-data" data-quiz-form novalidate>
                <?= csrf_field(); ?>
                <input type="hidden" name="latitude" data-quiz-latitude>
                <input type="hidden" name="longitude" data-quiz-longitude>

                <div class="quiz-progress" aria-label="Progresso do diagnostico">
                    <div>
                        <span data-quiz-counter>Etapa 1 de 13</span>
                        <strong data-quiz-title>Dados do cliente</strong>
                    </div>
                    <div class="quiz-progress__track">
                        <span data-quiz-progress></span>
                    </div>
                </div>

                <div class="quiz-feedback" data-quiz-feedback hidden></div>

                <fieldset class="quiz-step is-active" data-quiz-step data-step-title="Dados do cliente">
                    <legend>Dados do cliente</legend>
                    <p class="quiz-step__copy">Comece com os dados principais para retorno do diagnostico tecnico.</p>
                    <div class="quiz-fields">
                        <div class="field">
                            <label for="quiz-name">Nome</label>
                            <input class="input" id="quiz-name" name="name" type="text" autocomplete="name" required>
                        </div>
                        <div class="field">
                            <label for="quiz-phone">Telefone</label>
                            <input class="input" id="quiz-phone" name="phone" type="tel" autocomplete="tel" required>
                        </div>
                        <div class="field">
                            <label for="quiz-email">E-mail</label>
                            <input class="input" id="quiz-email" name="email" type="email" autocomplete="email" required>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="quiz-step" data-quiz-step data-step-title="Localização">
                    <legend>Localização</legend>
                    <p class="quiz-step__copy">Informe o endereço ou autorize sua localização para uma futura integração com OpenStreetMap e Nominatim.</p>
                    <div class="quiz-fields">
                        <div class="field">
                            <label for="quiz-address">Endereço manual</label>
                            <input class="input" id="quiz-address" name="address" type="text" autocomplete="street-address" placeholder="Rua, número, bairro e cidade" required>
                        </div>
                        <button class="btn btn--ghost" type="button" data-location-button>Usar minha localizacao</button>
                        <p class="quiz-location-status" data-location-status>Localização opcional. O endereço manual pode ser preenchido normalmente.</p>
                    </div>
                </fieldset>

                <fieldset class="quiz-step" data-quiz-step data-step-title="Tipo de imovel">
                    <legend>Tipo de imovel</legend>
                    <p class="quiz-step__copy">Selecione o contexto do atendimento.</p>
                    <div class="quiz-options">
                        <?php foreach (['Residencia', 'Condominio', 'Empresa', 'Comercio'] as $item): ?>
                            <label class="quiz-option">
                                <input type="radio" name="property_type" value="<?= e($item); ?>" required>
                                <span><?= e($item); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <fieldset class="quiz-step" data-quiz-step data-step-title="Superficies">
                    <legend>Superficies</legend>
                    <p class="quiz-step__copy">Marque uma ou mais areas que precisam de avaliacao.</p>
                    <div class="quiz-options quiz-options--multi">
                        <?php foreach (['Garagem', 'Muro', 'Pedra', 'Fachada', 'Telhado', 'Piscina', 'Deck', 'Area gourmet', 'Calcada', 'Outro'] as $item): ?>
                            <label class="quiz-option">
                                <input type="checkbox" name="surfaces[]" value="<?= e($item); ?>" data-required-group="surfaces">
                                <span><?= e($item); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <fieldset class="quiz-step" data-quiz-step data-step-title="Tipo de sujeira">
                    <legend>Tipo de sujeira</legend>
                    <p class="quiz-step__copy">Indique os sinais mais visiveis na superficie.</p>
                    <div class="quiz-options quiz-options--multi">
                        <?php foreach (['Lodo', 'Musgo', 'Mofo', 'Manchas'] as $item): ?>
                            <label class="quiz-option">
                                <input type="checkbox" name="dirt_types[]" value="<?= e($item); ?>" data-required-group="dirt_types">
                                <span><?= e($item); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <fieldset class="quiz-step" data-quiz-step data-step-title="Area aproximada">
                    <legend>Area aproximada</legend>
                    <p class="quiz-step__copy">Uma estimativa ajuda a orientar tempo, equipe e metodo.</p>
                    <div class="quiz-options">
                        <?php foreach (['Pequena', 'Media', 'Grande'] as $item): ?>
                            <label class="quiz-option">
                                <input type="radio" name="area_size" value="<?= e($item); ?>" required>
                                <span><?= e($item); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="field quiz-optional-field">
                        <label for="quiz-square-meters">Se souber, informe a metragem aproximada.</label>
                        <input class="input" id="quiz-square-meters" name="square_meters" type="number" min="1" inputmode="numeric" placeholder="Ex: 80">
                    </div>
                </fieldset>

                <fieldset class="quiz-step" data-quiz-step data-step-title="Dificuldade de acesso">
                    <legend>Dificuldade de acesso</legend>
                    <p class="quiz-step__copy">Avalie o acesso para a equipe técnica chegar e executar o serviço.</p>
                    <div class="quiz-options">
                        <?php foreach (['Facil', 'Medio', 'Dificil'] as $item): ?>
                            <label class="quiz-option">
                                <input type="radio" name="access_difficulty" value="<?= e($item); ?>" required>
                                <span><?= e($item); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <fieldset class="quiz-step" data-quiz-step data-step-title="Altura elevada">
                    <legend>Existe altura elevada?</legend>
                    <p class="quiz-step__copy">Essa informacao ajuda a prever seguranca, equipamento e tempo de execucao.</p>
                    <div class="quiz-options">
                        <?php foreach (['Sim', 'Nao'] as $item): ?>
                            <label class="quiz-option">
                                <input type="radio" name="elevated_height" value="<?= e($item); ?>" required>
                                <span><?= e($item); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <fieldset class="quiz-step" data-quiz-step data-step-title="Frequencia de limpeza">
                    <legend>Frequencia de limpeza</legend>
                    <p class="quiz-step__copy">A recorrencia indica o nivel de acumulacao e manutencao preventiva.</p>
                    <div class="quiz-options">
                        <?php foreach (['Nunca', 'Raramente', 'Frequentemente'] as $item): ?>
                            <label class="quiz-option">
                                <input type="radio" name="cleaning_frequency" value="<?= e($item); ?>" required>
                                <span><?= e($item); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <fieldset class="quiz-step" data-quiz-step data-step-title="Prioridade">
                    <legend>Prioridade</legend>
                    <p class="quiz-step__copy">Qual objetivo pesa mais na avaliacao?</p>
                    <div class="quiz-options">
                        <?php foreach (['Estetica', 'Seguranca', 'Valorizacao', 'Manutencao'] as $item): ?>
                            <label class="quiz-option">
                                <input type="radio" name="priority" value="<?= e($item); ?>" required>
                                <span><?= e($item); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <fieldset class="quiz-step" data-quiz-step data-step-title="Observações">
                    <legend>Observações</legend>
                    <p class="quiz-step__copy">Inclua detalhes importantes sobre manchas, material, urgencia, acesso ou expectativa visual.</p>
                    <div class="field">
                        <label for="quiz-notes">Observações técnicas</label>
                        <textarea class="textarea" id="quiz-notes" name="notes" placeholder="Descreva o que considera relevante para o diagnostico."></textarea>
                    </div>
                </fieldset>

                <fieldset class="quiz-step" data-quiz-step data-step-title="Upload de imagens">
                    <legend>Upload de imagens</legend>
                    <p class="quiz-step__copy">Envie ate 10 imagens. No celular, voce pode selecionar da galeria ou tirar uma foto na hora.</p>
                    <input class="sr-only" id="quiz-images" name="images[]" type="file" accept="image/png,image/jpeg,image/webp" multiple capture="environment" data-quiz-upload>
                    <label class="quiz-upload" for="quiz-images" data-quiz-dropzone>
                        <span>+</span>
                        <strong>Arraste imagens ou toque para enviar</strong>
                        <small>Ate 10 fotos para apoiar o diagnostico visual.</small>
                    </label>
                    <div class="quiz-upload-preview" data-quiz-preview></div>
                    <p class="quiz-upload-note">Estrutura preparada para compressao futura, thumbnails e analise visual por IA.</p>
                </fieldset>

                <fieldset class="quiz-step quiz-step--final" data-quiz-step data-step-title="Diagnostico recebido">
                    <legend>Recebemos seu diagnostico tecnico.</legend>
                    <p class="quiz-step__copy">As informacoes foram organizadas para uma avaliacao mais precisa. A equipe Galvao podera analisar o ambiente, entender as superficies e orientar o melhor caminho para a revitalizacao.</p>
                    <div class="quiz-summary">
                        <span class="badge badge--gold">Diagnostico registrado</span>
                        <span class="badge">Análise técnica</span>
                        <span class="badge">Retorno consultivo</span>
                    </div>
                </fieldset>

                <div class="quiz-loading" data-quiz-loading hidden>
                    <span></span>
                    <p>Organizando diagnostico tecnico...</p>
                </div>

                <div class="quiz-actions">
                    <button class="btn btn--ghost" type="button" data-quiz-prev disabled>Voltar</button>
                    <button class="btn btn--primary" type="button" data-quiz-next>Continuar</button>
                </div>
            </form>
        </div>
    </div>
</section>
