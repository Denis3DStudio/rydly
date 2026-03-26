<div class="container-fluid">
	<div class="row page-head">
		<div class="col-6 p-0">
			<h1><i class="fa fa-fw fa-shapes op-2"></i> Attributo</h1>
		</div>
	</div>

	<div class="row">
		<div class="col-lg-8">
			<div class="row">
				<div class="col">
					<nav>
						<div class="nav nav-tabs" id="nav-tab" role="tablist"></div>
					</nav>

					<div class="card mb-3 border-0">
						<div class="card-body">
							<div class="tab-content" id="nav-tabContent"></div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="col-lg-4">
			<div class="card mb-3 shadow">
				<div class="card-body">
					<div class="row">
						<div class="col-12" id="modifier_data">
							<div class="form-group">
								<span><b>Creato da:</b> <i name="Creator"></i></span><br />
								<span><b>Ultima modifica:</b> <i name="Modifier"></i></span>
							</div>
							<div class="form-group">
								<span><b>Creato il:</b> <i name="InsertDate"></i></span><br />
								<span><b>Ultima modifica:</b> <i name="UpdateDate"></i></span>
							</div>
						</div>
						<div class="col-12">
							<div class="form-group">
								<label>Tipologia</label>
								<select name="Type" id="Type" class="form-select" mandatory>
									<?php
									foreach (Base_Attribute_Type::ALL as $type) {
									?>
										<option value="<?= $type; ?>"> <?= Base_Attribute_Type::NAMES[$type]; ?>
										<?php
									}
										?>
								</select>
							</div>
						</div>
					</div>
				</div>
				<div class="card-footer">
					<div class="row align-items-center">
						<div class="col-6">
							<button type="button" class="btn btn-sm btn-link text-danger" id="deleteButton"
								onclick="deleteAttribute()" disabled>
								<i class="fas fa-trash"></i> Elimina
							</button>
						</div>

						<div class="col-6 text-end">
							<button type="button" class="btn btn-success" onclick="saveAttribute()">
								<i class="fas fa-fw fa-save"></i> Salva
							</button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script type="text/html" id="language_tab_template">
	<form
		class="tab-pane fade"
		id="tabLang-{{Language}}"
		name="tabLang"
		role="tabpanel"
		aria-labelledby="tabLang-{{Language}}-tab"
		language="{{Language}}">
		<div id="tabContent-{{Language}}" mandatory_fields_container>
			<div class="row">
				<div class="col-12">
					<div class="form-group">
						<label>Testo *</label>
						<input
							type="text"
							name="Text"
							id="Text-{{Language}}"
							class="form-control check"
							mandatory />
					</div>
				</div>
			</div>
		</div>
	</form>
</script>