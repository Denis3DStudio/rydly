<div class="container-fluid">
	<div class="row page-head">
		<div class="col p-0">
			<h1>
				<i class="fa fa-fw fa-tachometer-alt op-2"></i>
				Benvenuto, <span class="text-secondary"> <?= $this->Logged->Name ?> </span>
			</h1>
		</div>
	</div>

	<!-- <div class="row">

		<?php
		if (property_exists($this->__route, "News")) {
		?>
			<div class="col-12 col-md-6 col-xl-4">
				<div class="card border-0 mb-3 o-hidden shadow-sm">
					<div class="card-body">
						<h5 class="card-title">News</h5>
						<div class="card-body-icon">
							<i class="fa fa-fw fa-file-alt op-1"></i>
						</div>
						<div class="row">
							<div class="col">
								<ul class="list-unstyled m-0">
									<li><?= $this->__route->News->Count ?> Pubblicate</li>
									<li class="op-5"><?= $this->__route->News->CategoriesCount ?> Categorie</li>
								</ul>
							</div>
						</div>
					</div>
					<a class="card-footer" href="/backend/news/"> Gestisci News </a>
				</div>
			</div>
		<?php
		}
		?>
	</div>

	<hr />

	<div class="row">
		<?php
		if (property_exists($this->__route, "Products")) {
		?>
			<div class="col-12 col-md-6 col-xl-4">
				<div class="card border-0 mb-3 o-hidden shadow-sm">
					<div class="card-body">
						<h5 class="card-title">Prodotti</h5>
						<div class="card-body-icon">
							<i class="fa fa-fw fa-archive op-1"></i>
						</div>
						<div class="row">
							<div class="col-6">
								<ul class="list-unstyled m-0 border-right">
									<li><?= $this->__route->Products->Count ?> Pubblicati</li>
									<li class="op-5"><?= $this->__route->Products->CategoriesCount ?> Categorie</li>
									<li class="op-5"><?= $this->__route->Products->AttributesCount ?> Attributi</li>
								</ul>
							</div>
						</div>
					</div>
					<a class="card-footer" href="/backend/product/"> Gestisci Prodotti </a>
				</div>
			</div>
		<?php
		}
		?>
	</div> -->

</div>
<!-- container-fluid-->