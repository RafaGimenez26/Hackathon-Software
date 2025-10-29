🌾 AgroHub Misiones
Plataforma de Comercio Electrónico Local para Productores Agropecuarios de Misiones, Argentina
AgroHub Misiones es una aplicación web que conecta productores locales con consumidores de la provincia de Misiones, facilitando la venta directa de productos frescos, orgánicos y artesanales. La plataforma permite a los productores publicar sus productos, gestionar inventarios y administrar pedidos, mientras los clientes pueden explorar, filtrar y solicitar productos directamente.

📋 Características Principales:

Para Productores

👨‍🌾 Registro completo con información de producción, zonas de venta y métodos de pago
📦 Gestión de productos con imágenes, precios, stock y disponibilidad por días
📊 Dashboard con estadísticas de ventas y productos más vendidos
🛒 Sistema de pedidos con estados individuales por producto
📄 Generación de PDFs para remitos y comprobantes
🔔 Notificaciones de nuevos pedidos y cambios de estado

Para Clientes

🛍️ Catálogo de productos con búsqueda y filtros avanzados
🗺️ Filtros por zona, categoría, precio y día de disponibilidad
🛒 Carrito de compras persistente
📱 Historial de pedidos con seguimiento por producto
💬 Contacto directo con productores vía WhatsApp
♻️ Programa de reciclaje de envases con descuentos

Funcionalidades Técnicas

🔐 Autenticación segura con contraseñas hasheadas (bcrypt)
💾 Base de datos híbrida: MySQL + MongoDB
📦 Gestión de estados granular por producto en pedidos
📈 Gráficos interactivos con Chart.js
📄 Generación de PDFs con TCPDF
🎨 Diseño responsive con Bootstrap 5
☁️ Deploy en Railway con Docker


🛠️ Stack Tecnológico
Backend

PHP 8.3 - Lenguaje principal
MySQL - Base de datos relacional (productores, usuarios, catálogos)
MongoDB - Base de datos NoSQL (productos, carritos, pedidos)
Composer - Gestor de dependencias

Frontend

HTML5 / CSS3
Bootstrap 5.3 - Framework CSS
Bootstrap Icons - Iconografía
JavaScript Vanilla - Interactividad
Chart.js - Gráficos estadísticos

Librerías PHP

mongodb/mongodb - Driver oficial de MongoDB
vlucas/phpdotenv - Variables de entorno
tecnickcom/tcpdf - Generación de PDFs

DevOps

Docker - Containerización
Railway - Hosting y deploy automático
Git - Control de versiones
