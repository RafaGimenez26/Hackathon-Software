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

            // Funcionalidad de agregar al carrito (ejemplo básico)
            const botonesAgregar = document.querySelectorAll('.btn-agregar');
            botonesAgregar.forEach(btn => {
                btn.addEventListener('click', function() {
                    const productoId = this.dataset.productoId;
                    const nombre = this.dataset.nombre;
                    const precio = this.dataset.precio;
                    const input = document.querySelector(`input[data-producto-id="${productoId}"]`);
                    const cantidad = input.value;

                    // Aquí puedes agregar la lógica para guardar en el carrito
                    // Por ahora solo mostramos un mensaje
                    alert(`Agregado al carrito:\n${nombre}\nCantidad: ${cantidad}\nPrecio: $${precio}`);
                    
                    // Opcional: Cambiar el botón temporalmente
                    const iconoOriginal = this.innerHTML;
                    this.innerHTML = '<i class="bi bi-check-lg"></i> Agregado';
                    this.classList.add('btn-success');
                    this.classList.remove('btn-primary-custom');
                    
                    setTimeout(() => {
                        this.innerHTML = iconoOriginal;
                        this.classList.remove('btn-success');
                        this.classList.add('btn-primary-custom');
                    }, 2000);
                });
            });
        });