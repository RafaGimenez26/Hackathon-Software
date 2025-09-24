<?php 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mercado Agrícola Local - Ver Productos</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <div class="container-custom">
        <div class="header">
            <h1>🌾 Mercado Agrícola Local</h1>
            <p>Conectando productores locales con la comunidad Misionera</p>
        </div>

        <div class="nav-tabs-custom">
            <a class="tab-btn active" href="index.php">🛒 Ver Productos</a>
            <a class="tab-btn" href="registro.php">👨‍🌾 Registrarse como Productor</a>
            <a class="tab-btn" href="misproductos.php">📦 Mis Productos</a>
            <a class="tab-btn" href="mispedidos.php">🛍️ Mis Pedidos</a>
        </div>

        <!-- CONTENIDO DE PRODUCTOS -->
        <div>
            <h2 class="tab-title">Productos Frescos Disponibles</h2>
            <!-- contenido completo de la sección productos -->

            <div class="filters-section">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="filter-group">
                            <label class="form-label">🥬 Tipo de Producto</label>
                            <select class="form-select">
                                <option>Todos los productos</option>
                                <option>Verduras de hoja</option>
                                <option>Frutas de estación</option>
                                <option>Cereales y legumbres</option>
                                <option>Productos lácteos</option>
                                <option>Carnes y embutidos</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="filter-group">
                            <label class="form-label">📍 Zona de Posadas</label>
                            <select class="form-select">
                                <option>Todas las zonas</option>
                                <option>Villa Sarita</option>
                                <option>Villa Cabello</option>
                                <option>Itaembe Mini</option>
                                <option>Santa Rita</option>
                                <option>Villa Urquiza</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="filter-group">
                            <label class="form-label">📅 Disponibilidad</label>
                            <select class="form-select">
                                <option>Cualquier día</option>
                                <option>Lunes a Viernes</option>
                                <option>Fines de semana</option>
                                <option>Solo Sábados</option>
                                <option>Solo Domingos</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="filter-group">
                            <label class="form-label">💰 Rango de Precio</label>
                            <input type="range" class="form-range" min="0" max="5000" value="2500">
                            <small class="text-muted">Hasta $2500 por kg</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="productos-header">
                <div class="productos-count">
                    <strong>24 productos encontrados</strong>
                </div>
                <div class="view-toggle">
                    <button class="view-btn active">🔲 Grilla</button>
                    <button class="view-btn">📋 Lista</button>
                </div>
            </div>

            <div class="row g-3">
                <!-- Producto 1 -->
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="producto-card">
                        <img src="img/tomates.jpg" alt="Tomates Cherry Orgánicos" class="product-image-placeholder" />

                        <div class="producto-header">
                            <div>
                                <h3 class="producto-title">Tomates Cherry Orgánicos</h3>
                                <span class="producto-tipo">Verduras</span>
                            </div>
                            <div class="precio">$1.200/kg</div>
                        </div>

                        <div class="productor-info">
                            <div class="ubicacion">
                                <i class="bi bi-person-badge"></i>
                                <strong>Granja Los Álamos</strong>
                            </div>
                            <div class="ubicacion">
                                <i class="bi bi-geo-alt"></i>
                                Itaembe Mini
                            </div>
                            <div class="ubicacion">
                                <i class="bi bi-telephone"></i>
                                +54 3743 456-789
                            </div>
                        </div>

                        <p class="product-description">Tomates cherry cultivados sin pesticidas, ideales para ensaladas. Cosecha de esta semana.</p>

                        <div class="disponibilidad">
                            🕐 Sábados y Domingos, 9:00 - 17:00
                        </div>

                        <div class="cantidad-selector">
                            <span>Cantidad:</span>
                            <input type="number" value="1" min="1" max="15" class="cantidad-input form-control">
                            <span>kg</span>
                            <button class="btn btn-primary-custom btn-sm-custom">
                                <i class="bi bi-cart-plus"></i> Agregar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Producto 2 -->
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="producto-card">
                        <img src="img/lechuga.jpg" alt="Lechuga Orgánica" class="product-image-placeholder" />

                        <div class="producto-header">
                            <div>
                                <h3 class="producto-title">Lechuga Criolla</h3>
                                <span class="producto-tipo">Verduras</span>
                            </div>
                            <div class="precio">$800/u</div>
                        </div>

                        <div class="productor-info">
                            <div class="ubicacion">
                                <i class="bi bi-person-badge"></i>
                                <strong>Quinta El Sol</strong>
                            </div>
                            <div class="ubicacion">
                                <i class="bi bi-geo-alt"></i>
                                Santa Rita
                            </div>
                            <div class="ubicacion">
                                <i class="bi bi-telephone"></i>
                                +54 3763 567-890
                            </div>
                        </div>

                        <p class="product-description">Lechuga fresca de hoja verde, perfecta para ensaladas mixtas. Cultivo hidropónico.</p>

                        <div class="disponibilidad">
                            🕐 Mar, Jue y Sáb, 8:00 - 16:00
                        </div>

                        <div class="cantidad-selector">
                            <span>Cantidad:</span>
                            <input type="number" value="1" min="1" max="20" class="cantidad-input form-control">
                            <span>u</span>
                            <button class="btn btn-primary-custom btn-sm-custom">
                                <i class="bi bi-cart-plus"></i> Agregar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Producto 3 -->
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="producto-card">
                        <img src="img/queso.jpg" alt="Queso Criollo" class="product-image-placeholder" />

                        <div class="producto-header">
                            <div>
                                <h3 class="producto-title">Queso Criollo</h3>
                                <span class="producto-tipo">Lácteos</span>
                            </div>
                            <div class="precio">$9.500/kg</div>
                        </div>

                        <div class="productor-info">
                            <div class="ubicacion">
                                <i class="bi bi-person-badge"></i>
                                <strong>Tambo La Esperanza</strong>
                            </div>
                            <div class="ubicacion">
                                <i class="bi bi-geo-alt"></i>
                                Villa Urquiza
                            </div>
                            <div class="ubicacion">
                                <i class="bi bi-telephone"></i>
                                +54 3764 678-901
                            </div>
                        </div>

                        <p class="product-description">Queso cremoso, elaborado artesanalmente. Sin conservantes ni aditivos químicos.</p>

                        <div class="disponibilidad">
                            🕐 Vie, Sáb y Dom, 5:00 - 12:00
                        </div>

                        <div class="cantidad-selector">
                            <span>Cantidad:</span>
                            <input type="number" value="1" min="1" max="5" class="cantidad-input form-control">
                            <span>kg</span>
                            <button class="btn btn-primary-custom btn-sm-custom">
                                <i class="bi bi-cart-plus"></i> Agregar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Producto 4 -->
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="producto-card">
                        <img src="img/miel.jpg" alt="Miel de abeja" class="product-image-placeholder" />

                        <div class="producto-header">
                            <div>
                                <h3 class="producto-title">Miel de Abeja</h3>
                                <span class="producto-tipo">Envasados</span>
                            </div>
                            <div class="precio">$3.200/kg</div>
                        </div>

                        <div class="productor-info">
                            <div class="ubicacion">
                                <i class="bi bi-person-badge"></i>
                                <strong>Apiario San Martín</strong>
                            </div>
                            <div class="ubicacion">
                                <i class="bi bi-geo-alt"></i>
                                Villa Sarita
                            </div>
                            <div class="ubicacion">
                                <i class="bi bi-telephone"></i>
                                +54 3743 789-012
                            </div>
                        </div>

                        <p class="product-description">Miel pura, extraída artesanalmente. Rica en propiedades medicinales.</p>

                        <div class="disponibilidad">
                            🕐 Lun a Vie, 10:00 - 18:00
                        </div>

                        <div class="cantidad-selector">
                            <span>Cantidad:</span>
                            <input type="number" value="1" min="1" max="10" class="cantidad-input form-control">
                            <span>kg</span>
                            <button class="btn btn-primary-custom btn-sm-custom">
                                <i class="bi bi-cart-plus"></i> Agregar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const productCards = document.querySelectorAll('.producto-card');
            productCards.forEach(card => {
                card.addEventListener('mouseenter', () => card.style.transform = 'translateY(-5px)');
                card.addEventListener('mouseleave', () => card.style.transform = 'translateY(0)');
            });
        });
    </script>
</body>
</html>
