# Inventra - Lista de Pendientes (TODO)

## 🏗️ Propiedades y Entidades (Scoping)
- [ ] **Vincular Sucursales a Proveedores y Productos**:
    - Limitar la visibilidad de productos por sucursal asignada.
    - Evitar saturación de datos irrelevantes para usuarios de sucursales específicas.
- [ ] **Nuevo Rol**: Implementar el rol de `Vendedor` (rol terciario).

## 🛒 Sistema de Comandas (Pre-ventas / Carritos)
- [ ] **Creación de la entidad `Comanda`**:
    - Permitir la creación de tickets de compra sin cierre de venta.
    - Flujo: Comanda -> Responsable de Caja (para cierre y cobro).
    - Registrar quién es el "Responsable de Caja" activo (quien abrió el turno).
    - Permitir asignar un ID de comisión si la comanda la genera un rol distinto al cajero.
- [ ] **Funcionalidad en Tiempo Real**:
    - Reflejar instantáneamente las comandas/regalías creadas en la pantalla del cajero.
    - Posibilidad de asignar/cambiar manualmente al responsable de caja.

## 🎫 Lógica de Tickets y Productos
- [ ] **Control de Precios Manual**:
    - Permitir aumentar el precio de un producto manualmente durante la venta.
    - **Restricción**: Nunca permitir un precio menor al base (solo mayor).
- [ ] **Justificaciones de Salida (Regalías/Salidas)**:
    - Opción de imprimir la justificación en el ticket final (ej: "Registrar Salida de Mercancía").
    - Permitir a usuarios de alto nivel enviar "Salidas" a caja con valor $0 (Motivo: Regalía, etc.) a través del sistema de comandas.

## 📱 Interfaz y UX/UI
- [ ] **Optimización Móvil/Tablet**: Mejorar la responsividad y usabilidad en dispositivos táctiles.
- [ ] **Selector de Sucursal**: Hacer más explícita y clara la selección de sucursal durante el inicio de sesión.

## 📊 Reportes y Comisiones
- [ ] **Mejora en Reporte de Comisiones**:
    - Habilitar opción para ordenar/agrupar comisiones por **Nombre de Comisión** en lugar de solo por Tipo de Agencia.

## 🔧 Refactorización y Mejora Técnica
- [ ] **Soporte Offline**: Implementar funcionalidad sin internet (PWA + Dexie.js + Sync Queue).
- [ ] **Refactorización General**:
    - Optimizar consultas a la base de datos.
    - Robustecer el sistema de permisos y reglas de acceso.

## 🐛 Bugs detectados
- [ ] **Actualización en Tiempo Real**: Fixear el delay en el módulo de comisiones (evitar recarga manual).
- [ ] **Claridad en Comisiones de Llegada**:
    - Mostrar el **Nombre del Comisionista** y la **Hora** en el POS, en lugar de solo el ID.
