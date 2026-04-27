# Inventra - Lista de Pendientes (TODO)

## 🏗️ Propiedades y Entidades (Scoping)
- [ ] **Vincular Sucursales a Proveedores y Productos**:
    - Limitar la visibilidad de productos por sucursal asignada.
    - Evitar saturación de datos irrelevantes para usuarios de sucursales específicas.
- [ ] **Nuevo Rol**: Implementar el rol de `Vendedor` (rol terciario).

## 🛒 Sistema de Preventas (Flujo tipo Orden/Comanda)
- [ ] **Creación de la entidad `Preventa`**:
    - **Concepto**: Funciona como una "orden de comida". El personal de piso genera la orden y el cajero la visualiza para procesarla.
    - **Lógica de Generación**: Permite crear pre-tickets (carritos) sin cerrar la venta.
    - **Rol del Cajero**: El responsable de caja recibe estas preventas como pestañas pendientes. Su función es cobrar y tiene la autoridad de **completar o ajustar los comisionistas** antes del cierre final.
    - **Registro de Responsabilidad**: Identificar al "Responsable de Caja" activo (persona asignada al turno/caja mediante permiso).
    - **Comisiones**: Quienes generen el pre-checkin (vendedores/PR) pueden asignar IDs de comisión iniciales, que luego el cajero valida.
- [ ] **Funcionalidad en Tiempo Real**:
    - Reflejar instantáneamente las preventas/regalías creadas en la pantalla del cajero.
    - Posibilidad de asignar/cambiar manualmente al responsable de caja.

## 🎫 Lógica de Tickets y Productos
- [ ] **Control de Precios Manual**:
    - Permitir aumentar el precio de un producto manualmente durante la venta.
    - **Restricción**: Nunca permitir un precio menor al base (solo mayor).
- [ ] **Justificaciones de Salida (Regalías/Salidas)**:
    - Opción de imprimir la justificación en el ticket final (ej: "Registrar Salida de Mercancía").
    - Permitir a usuarios de alto nivel enviar "Salidas" a caja con valor $0 (Motivo: Regalía, etc.) a través del sistema de pre-checkin.

## 📱 Interfaz y UX/UI
- [ ] **Optimización Móvil/Tablet**: Mejorar la responsividad y usabilidad en dispositivos táctiles.
- [ ] **Selector de Sucursal**: Hacer más explícita y clara la selección de sucursal durante el inicio de sesión.

## 📊 Reportes y Comisiones
- [ ] **Mejora en Reporte de Comisiones**:
    - Habilitar opción para ordenar/agrupar comisiones por **Nombre de Comisionista** en lugar de solo por Tipo de Agencia.

## 🔧 Refactorización y Mejora Técnica
- [ ] **Soporte Offline**: Implementar funcionalidad sin internet (PWA + Dexie.js + Sync Queue).
- [ ] **Refactorización General**:
    - Optimizar consultas a la base de datos.
    - Robustecer el sistema de permisos y reglas de acceso.

## 🐛 Bugs detectados
- [ ] **Actualización en Tiempo Real**: Fixear el delay en el módulo de comisiones (evitar recarga manual).
- [ ] **Claridad en Comisiones de Llegada**:
    - Mostrar el **Nombre del Comisionista** y la **Hora** en el POS, en lugar de solo el ID.
