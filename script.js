// Funcionalidad de agregar al carrito
        const botonesAgregar = document.querySelectorAll('.btn-agregar');
        botonesAgregar.forEach(btn => {
            btn.addEventListener('click', function() {
                const productoId = this.dataset.productoId;
                const input = document.querySelector(`input[data-producto-id="${productoId}"]`);
                const cantidad = input.value;

                fetch('carrito.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `producto_id=${productoId}&cantidad=${cantidad}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                    } else {
                        this.innerHTML = '<i class="bi bi-check-lg"></i> Agregado';
                        this.classList.add('btn-success');
                        setTimeout(() => location.reload(), 1000);
                    }
                })
                .catch(() => alert("Error al agregar al carrito."));
            });
        });

        // Actualizar label del precio
        function updatePrecioLabel(value) {
            document.getElementById('precioLabel').textContent = 
                'Hasta $' + parseInt(value).toLocaleString('es-AR') + ' por unidad';
        }

        // Enviar formulario cuando se suelta el rango de precio
        document.getElementById('precioRange').addEventListener('change', function() {
            document.getElementById('filtrosForm').submit();
        });

        // Animación de hover en las cards
        document.addEventListener('DOMContentLoaded', function() {
            const productCards = document.querySelectorAll('.producto-card');
            productCards.forEach(card => {
                card.addEventListener('mouseenter', () => card.style.transform = 'translateY(-5px)');
                card.addEventListener('mouseleave', () => card.style.transform = 'translateY(0)');
            });
        });

        const inputBusqueda = document.getElementById('busquedaInput');
    const spinner = document.getElementById('busquedaSpinner');
    const mensaje = document.getElementById('busquedaMensaje');
    const gridProductos = document.getElementById('productos-grid');
    const btnLimpiar = document.getElementById('btnLimpiarBusqueda');

    let timeoutBusqueda = null;

    inputBusqueda.addEventListener('input', function() {
        const termino = this.value.trim();

        clearTimeout(timeoutBusqueda);

        // Si se borra el texto, recargar productos originales
        if (termino.length === 0) {
            mensaje.classList.add('d-none');
            spinner.classList.add('d-none');
            location.reload();
            return;
        }

        // Esperar 500ms antes de buscar
        timeoutBusqueda = setTimeout(() => {
            buscarProductos(termino);
        }, 500);
    });

    btnLimpiar.addEventListener('click', () => {
        inputBusqueda.value = '';
        mensaje.classList.add('d-none');
        spinner.classList.add('d-none');
        location.reload();
    });

    function buscarProductos(termino) {
        spinner.classList.remove('d-none');
        mensaje.classList.add('d-none');

        fetch(`buscar_productos.php?q=${encodeURIComponent(termino)}`)
            .then(res => res.json())
            .then(data => {
                spinner.classList.add('d-none');
                gridProductos.innerHTML = '';

                if (data.error) {
                    mensaje.classList.remove('d-none');
                    mensaje.innerHTML = `<div class="alert alert-danger">${data.mensaje}</div>`;
                    return;
                }

                if (data.productos.length === 0) {
                    mensaje.classList.remove('d-none');
                    mensaje.innerHTML = `<div class="alert alert-warning text-center">
                        <i class="bi bi-exclamation-circle"></i> No se encontraron productos.
                    </div>`;
                    return;
                }

                mensaje.classList.remove('d-none');
                mensaje.innerHTML = `<div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> Se encontraron ${data.total} productos.
                </div>`;

                data.productos.forEach(prod => {
                    const card = document.createElement('div');
                    card.className = 'col-xl-3 col-lg-4 col-md-6';
                    card.innerHTML = `
                        <div class="producto-card">
                            <img src="${prod.imagen}" alt="${prod.nombre}" class="product-image-placeholder" onerror="this.src='img/default.jpg'" />
                            <div class="producto-header">
                                <div>
                                    <h3 class="producto-title">${prod.nombre}</h3>
                                    <span class="producto-tipo">${prod.categoria}</span>
                                </div>
                                <div class="precio">$${prod.precio.toLocaleString('es-AR')}/${prod.unidad}</div>
                            </div>
                            <div class="productor-info">
                                <div class="ubicacion">
                                    <i class="bi bi-shop"></i>
                                    <strong>${prod.punto_venta}</strong>
                                </div>
                                <div class="ubicacion">
                                    <i class="bi bi-geo-alt"></i>
                                    ${prod.direccion || ''}
                                </div>
                            </div>
                            <p class="product-description">${prod.descripcion || ''}</p>
                            <div class="disponibilidad">
                                🕐 ${Array.isArray(prod.dias_disponibles) ? prod.dias_disponibles.join(', ') : ''}, ${prod.horario || ''}
                            </div>
                            <div class="stock-info mb-2">
                                <small class="text-muted">
                                    <i class="bi bi-box-seam"></i> Stock: ${prod.stock_disponible} ${prod.unidad}
                                </small>
                            </div>
                        </div>
                    `;
                    gridProductos.appendChild(card);
                });
            })
            .catch(() => {
                spinner.classList.add('d-none');
                mensaje.classList.remove('d-none');
                mensaje.innerHTML = `<div class="alert alert-danger">
                    <i class="bi bi-x-circle"></i> Error al realizar la búsqueda.
                </div>`;
            });
    }