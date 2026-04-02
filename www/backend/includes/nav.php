<nav class="navbar navbar-expand-lg navbar-main fixed-top" id="mainNav">
    <div class="nav-resize-button d-none d-lg-block">
        <button id="js-navResizeButton" type="button">
            <div class="icon"></div>
        </button>
    </div>

    <div class="row no-gutters">
        <div class="col col-lg-12">
            <div class="navbar-brand">
                <div class="brand-title">
                    <?= SITE_NAME ?>
                </div>
            </div>
        </div>
    </div>

    <button class="navbar-toggler navbar-toggler-right" type="button" data-bs-toggle="collapse" data-bs-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
        <i class="fa fa-fw fa-bars"></i>
    </button>
    <div class="collapse navbar-collapse" id="navbarResponsive">
        <ul class="navbar-nav navbar-sidenav" id="sidebarMenu">

            <li class="nav-item">
                <a class="nav-link collapsed" href="<?= ACTIVE_PATH ?>/dashboard/">
                    <i class="fa fa-fw fa-tachometer-alt"></i> <span class="nav-link-text">Dashboard</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link nav-link-collapse collapsed" data-bs-toggle="collapse" href="#c-customer" data-parent="#sidebarMenu">
                    <i class="fa fa-fw fa-users"></i> <span class="nav-link-text">Clienti</span>
                </a>
                <ul class="sidenav-second-level collapse " id="c-customer">
                    <li class="nav-item">
                        <a class="nav-link collapsed" href="<?= ACTIVE_PATH ?>/customer/">
                            <span class="nav-link-text">Elenco</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link collapsed" href="<?= ACTIVE_PATH ?>/newsletter/">
                            <span class="nav-link-text">Newsletter</span>
                        </a>
                    </li>

                </ul>
            </li>

            <!-- <li class="nav-item">
                <a class="nav-link collapsed" href="<?= ACTIVE_PATH ?>/sponsor/">
                    <i class="fa fa-fw fa-ticket"></i> <span class="nav-link-text">Sponsor</span>
                </a>
            </li> -->

            <li class="nav-item">
                <a class="nav-link nav-link-collapse collapsed" data-bs-toggle="collapse" href="#c-sponsor" data-parent="#sidebarMenu">
                    <i class="fa fa-fw fa-ticket"></i> <span class="nav-link-text">Sponsor</span>
                </a>
                <ul class="sidenav-second-level collapse" id="c-sponsor">
                    <li class="nav-item">
                        <a class="nav-link collapsed" href="<?= ACTIVE_PATH ?>/sponsor/">
                            <span class="nav-link-text">Elenco</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link collapsed" href="<?= ACTIVE_PATH ?>/category_sponsor/">
                            <span class="nav-link-text">Categorie</span>
                        </a>
                    </li>
                </ul>
            </li>

            <li class="nav-item" style="display: none;">
                <a class="nav-link nav-link-collapse collapsed" data-bs-toggle="collapse" href="#c-news" data-parent="#sidebarMenu">
                    <i class="fa fa-fw fa-newspaper"></i> <span class="nav-link-text">Blog</span>
                </a>
                <ul class="sidenav-second-level collapse" id="c-news">
                    <li class="nav-item">
                        <a class="nav-link collapsed" href="<?= ACTIVE_PATH ?>/news/">
                            <span class="nav-link-text">Elenco</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link collapsed" href="<?= ACTIVE_PATH ?>/category/">
                            <span class="nav-link-text">Categorie</span>
                        </a>
                    </li>
                </ul>
            </li>

            <li class="nav-item">
                <a class="nav-link nav-link-collapse collapsed" data-bs-toggle="collapse" href="#c-place" data-parent="#sidebarMenu">
                    <i class="fa fa-fw fa-location-dot"></i> <span class="nav-link-text">Eventi</span>
                </a>
                <ul class="sidenav-second-level collapse" id="c-place">
                    <li class="nav-item">
                        <a class="nav-link collapsed" href="<?= ACTIVE_PATH ?>/event/">
                            <span class="nav-link-text">Elenco</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link collapsed" href="<?= ACTIVE_PATH ?>/category_event/">
                            <span class="nav-link-text">Categorie</span>
                        </a>
                    </li>
                </ul>
            </li>

            <li class="nav-item" style="display: none">
                <a class="nav-link collapsed" href="<?= ACTIVE_PATH ?>/survey/">
                    <i class="fa fa-fw fa-square-poll-horizontal"></i> <span class="nav-link-text">Sondaggi</span>
                </a>
            </li>

            <li class="nav-item" style="display: none;">
                <a class="nav-link collapsed" href="<?= ACTIVE_PATH ?>/coupon/">
                    <i class="fa fa-fw fa-tags"></i> <span class="nav-link-text">Codici Sconto</span>
                </a>
            </li>

            <li class="nav-item nav-title">GESTIONE</li>

            <li class="nav-item">
                <a class="nav-link nav-link-collapse collapsed" data-bs-toggle="collapse" href="#c-organization" data-parent="#sidebarMenu">
                    <i class="fa fa-fw fa-user-tie"></i> <span class="nav-link-text">Organizzator<?= Base_Account::isOrganizationMember($this->Logged->IdRole) ? "e" : "i" ?></span>
                </a>
                <ul class="sidenav-second-level collapse" id="c-organization">
                    <li class="nav-item">
                        <a class="nav-link collapsed" href="<?= ACTIVE_PATH ?>/organization/<?= Base_Account::isOrganizationMember($this->Logged->IdRole) ? $this->Logged->IdOrganization : '' ?>">
                            <span class="nav-link-text"><?= Base_Account::isOrganizationMember($this->Logged->IdRole) ? "Dettaglio" : "Elenco" ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link collapsed" href="<?= ACTIVE_PATH ?>/category_organization/">
                            <span class="nav-link-text">Categorie</span>
                        </a>
                    </li>
                </ul>
            </li>

            <li class="nav-item">
                <a class="nav-link collapsed" href="<?= ACTIVE_PATH ?>/account/<?= (!in_array($this->Logged->IdRole, Base_Account::ADMINS)) ? $this->Logged->IdAccount : '' ?>">
                    <i class="fa fa-fw fa-user-circle"></i> <span class="nav-link-text"><?= (!in_array($this->Logged->IdRole, Base_Account::ADMINS)) ? "Utente" : 'Utenti' ?></span>
                </a>
            </li>

            <li class="nav-item nav-title" data-role="4">DEV</li>

            <li class="nav-item" data-role="4">
                <a class="nav-link collapsed" href="<?= ACTIVE_PATH ?>/translation/">
                    <i class="fa fa-fw fa-language"></i> <span class="nav-link-text">Traduzioni</span>
                </a>
            </li>

            <li class="nav-item" data-role="4">
                <a class="nav-link collapsed" href="<?= ACTIVE_PATH ?>/email/">
                    <i class="fa fa-fw fa-road"></i> <span class="nav-link-text">Coda Email</span>
                </a>
            </li>

            <?php
            if ($this->Logged->IdRole == Base_Account::SUPERADMIN) {
            ?>
                <li class="nav-item nav-title">Azioni</li>

                <li class="nav-item">
                    <button type="button" class="nav-link" onclick="clearAllCache()">
                        <i class="fa fa-fw fa-eraser"></i> Pulisci tutta la cache
                    </button>
                </li>

                <li class="nav-item">
                    <div class="nav-link">
                        <div class="dark-switch ps-2">
                            <div class="form-check mb-0">
                                <input type="checkbox" class="form-check-input" id="themeSwitch">
                                <label class="custom-control-label" for="themeSwitch">
                                    <i class="fa fa-fw fa-moon"></i>
                                    <span class="nav-link-text">Dark Theme</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </li>

            <?php
            }
            ?>

            <li class="nav-item nav-item--bottom text-center">
                <div class="row no-gutters">
                    <div class="col-12 col-md-3">
                        <button type="button" class="nav-link bg-danger" onclick="logout()">
                            <i class="fa fa-fw fa-power-off text-white"></i>
                        </button>
                    </div>
                </div>
            </li>

        </ul>
    </div>
</nav>