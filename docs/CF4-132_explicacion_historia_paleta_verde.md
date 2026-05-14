# CF4-132 — Aplicar nueva paleta de colores verde en interfaces del admin y cliente

## 1. Resumen general de la historia

La historia **CF4-132** tiene como objetivo aplicar una nueva identidad visual verde en todo el sistema **Ciclo Finca 4 / Ciclo Pérez Catálogo**, tanto en la interfaz pública del cliente como en el panel administrativo.

El resumen de la tarea en Jira es:

> Como desarrollador quiero aplicar nueva paleta de colores verde en las interfaces del admin y del cliente.

Esta historia no busca crear una nueva funcionalidad de negocio como carrito, pedidos, inventario, proveedores o reportes. Su propósito principal es realizar una mejora visual global para que el sistema tenga una apariencia más profesional, moderna y coherente.

La tarea consiste en tomar una paleta de seis colores verdes y aplicarla en los elementos visuales principales del sistema, evitando que existan colores antiguos, colores mezclados o estilos puestos directamente en cada componente sin control.

La paleta definida es:

```css
#051F20
#0B2B26
#163832
#235347
#8EB69B
#DAF1DE
```

El resultado esperado es que, al entrar al sistema, tanto el cliente como el administrador perciban que están usando una misma plataforma, con una identidad gráfica clara, limpia y consistente.

---

## 2. Historia de usuario explicada

La historia puede interpretarse así:

**Como equipo de producto, quiero aplicar la nueva paleta de colores en todo el sistema, para tener una identidad visual unificada, moderna y coherente.**

### Como equipo de producto

Esto significa que la necesidad no viene de un único usuario final, sino del proyecto en general. El equipo quiere que el sistema deje de verse como una suma de pantallas separadas y pase a tener una identidad visual definida.

El equipo de producto busca que la aplicación transmita:

- Profesionalismo.
- Orden visual.
- Confianza.
- Coherencia entre módulos.
- Relación con la marca o concepto visual del proyecto.
- Mejor experiencia para el cliente y para el administrador.

### Quiero aplicar la nueva paleta de colores

Esto significa que los colores definidos deben convertirse en la base visual del sistema. No se trata solo de cambiar un botón o un header, sino de establecer una paleta oficial y reutilizable.

La paleta debe aplicarse en:

- Fondos.
- Botones.
- Headers.
- Sidebar.
- Links.
- Estados activos.
- Estados hover.
- Bordes.
- Badges.
- Cards.
- Tablas.
- Formularios.
- Elementos del catálogo.
- Elementos del panel administrativo.

### En todo el sistema

La historia menciona explícitamente que debe aplicarse tanto en el **admin** como en el **cliente**.

Eso quiere decir que se deben revisar al menos dos zonas principales:

1. **Interfaz del cliente**
   - Página principal.
   - Catálogo.
   - Cards de productos.
   - Botones de acción.
   - Links.
   - Fondo general.
   - Navegación.
   - Carrito si aplica.

2. **Interfaz administrativa**
   - Header.
   - Sidebar.
   - Menú activo.
   - Botones.
   - Tablas.
   - Formularios.
   - Cards de resumen.
   - Paneles.
   - Estados visuales.

### Para tener una identidad visual unificada, moderna y coherente

Este es el beneficio real de la historia.

La aplicación debe sentirse como un solo producto. No debe parecer que la parte pública fue diseñada con una idea y el admin con otra. Tampoco debe parecer que cada pantalla fue hecha con colores al azar.

La coherencia visual ayuda a que el sistema sea más fácil de usar, más confiable y más presentable para entregas académicas, demostraciones, revisiones del proyecto y uso real por parte de Ciclo Pérez.

---

## 3. Problema que resuelve la historia

El problema principal es la falta de estandarización visual.

Cuando un sistema crece, es común que los desarrolladores agreguen colores directamente en diferentes componentes, por ejemplo:

```html
<button class="bg-green-600">
```

o:

```css
background-color: #16a34a;
```

Esto genera varios problemas:

- Cada pantalla puede verse diferente.
- Hay botones con tonos distintos.
- El admin puede no verse relacionado con la tienda cliente.
- Se dificulta mantener el diseño.
- Cambiar la marca visual en el futuro se vuelve más difícil.
- El código queda menos limpio.
- Se pierde consistencia en hover, estados activos y fondos.

La historia CF4-132 busca resolver eso creando una paleta base y aplicándola globalmente.

---

## 4. Usuario o área beneficiada

Aunque la historia está escrita desde el punto de vista del equipo de producto, beneficia a varios perfiles.

### Cliente

El cliente tendrá una tienda visualmente más agradable, ordenada y confiable. Esto es importante porque el catálogo es la primera impresión que tendrá del negocio.

Una interfaz con colores bien aplicados puede ayudar a que el cliente:

- Navegue con más comodidad.
- Identifique botones importantes.
- Distinga acciones principales.
- Perciba el sistema como más profesional.
- Confíe más en el catálogo digital.

### Administrador

El administrador tendrá un panel más claro y consistente. Esto ayuda especialmente en tareas repetitivas como gestión de productos, inventario, pedidos o proveedores.

Una buena paleta permite:

- Diferenciar zonas importantes.
- Ubicar mejor menús activos.
- Reconocer botones principales.
- Reducir desorden visual.
- Mejorar la experiencia en el panel interno.

### Equipo de desarrollo

El equipo de desarrollo también se beneficia porque al definir variables o colores en Tailwind, el mantenimiento se vuelve más sencillo.

En vez de buscar colores por todo el código, se centraliza la paleta en un solo lugar.

---

## 5. Paleta de colores explicada

La paleta está formada por seis tonos verdes, desde el más oscuro hasta el más claro.

Cada color tiene una función visual recomendada.

---

### 5.1 `#051F20` — `--color-darkest`

Este es el color más oscuro de la paleta.

Debe utilizarse para zonas de mayor peso visual, como:

- Navbar principal.
- Sidebar muy oscuro.
- Footer.
- Fondos de alto contraste.
- Contenedores principales oscuros.
- Overlays.
- Elementos que necesiten profundidad.

Ejemplo de uso:

```css
background-color: var(--color-darkest);
```

Este color no debería usarse como fondo general de toda la aplicación cliente, porque puede hacer que la experiencia se sienta demasiado pesada. Es mejor reservarlo para zonas específicas donde se necesita contraste fuerte.

---

### 5.2 `#0B2B26` — `--color-dark`

Este color también es oscuro, pero menos intenso que `#051F20`.

Debe utilizarse para:

- Header del administrador.
- Sidebar del administrador.
- Barras superiores.
- Menús laterales.
- Cards oscuras.
- Fondos administrativos.
- Elementos principales del layout interno.

La historia pide específicamente que el **header del admin** use este color.

Ejemplo:

```css
.admin-header {
  background-color: var(--color-dark);
}
```

---

### 5.3 `#163832` — `--color-medium-dark`

Este es un verde oscuro intermedio.

Debe utilizarse para estados de interacción, especialmente:

- Hover en elementos oscuros.
- Botones oscuros al pasar el mouse.
- Menús desplegables.
- Estados seleccionados.
- Filas activas.
- Elementos secundarios en fondos oscuros.

Ejemplo:

```css
.button-primary:hover {
  background-color: var(--color-medium-dark);
}
```

Este color ayuda a que el sistema tenga interacciones visibles sin usar colores externos a la paleta.

---

### 5.4 `#235347` — `--color-medium`

Este es el color principal de acción.

Debe utilizarse para:

- Botones primarios.
- Botones de guardar.
- Botones de confirmar.
- Botones de iniciar sesión.
- Botones de añadir al carrito.
- Acciones principales.
- Acentos importantes.

La historia pide que los botones primarios usen este color.

Ejemplos de botones primarios:

- Guardar.
- Confirmar pedido.
- Crear producto.
- Actualizar.
- Añadir al carrito.
- Iniciar sesión.
- Generar reporte.

Ejemplo CSS:

```css
.button-primary {
  background-color: var(--color-medium);
  color: white;
}
```

---

### 5.5 `#8EB69B` — `--color-light`

Este es un verde claro intermedio.

Debe utilizarse para:

- Bordes.
- Íconos secundarios.
- Links activos.
- Estados hover de enlaces.
- Indicadores visuales.
- Líneas divisorias.
- Detalles decorativos.
- Badges suaves.

La historia pide que los **links activos y hover** usen este color.

Ejemplo:

```css
.nav-link:hover,
.nav-link.active {
  color: var(--color-light);
}
```

Hay que tener cuidado de no usar este color como texto sobre fondos muy claros, porque puede perder contraste.

---

### 5.6 `#DAF1DE` — `--color-lightest`

Este es el color más claro de la paleta.

Debe utilizarse para:

- Fondo general de la tienda cliente.
- Fondos suaves.
- Badges claros.
- Secciones informativas.
- Tarjetas suaves.
- Fondos de áreas secundarias.

La historia pide que el fondo de la tienda cliente use este color como fondo suave.

Ejemplo:

```css
.client-store {
  background-color: var(--color-lightest);
}
```

Este color funciona bien como fondo si el texto principal es oscuro, por ejemplo `#051F20` o `#0B2B26`.

---

## 6. Definición técnica esperada

Lo correcto es que la paleta se defina en un lugar central del proyecto.

Si el proyecto usa CSS global, se puede definir en `:root`.

```css
:root {
  --color-darkest: #051F20;
  --color-dark: #0B2B26;
  --color-medium-dark: #163832;
  --color-medium: #235347;
  --color-light: #8EB69B;
  --color-lightest: #DAF1DE;
}
```

Esto permite usar los colores así:

```css
body {
  background-color: var(--color-lightest);
  color: var(--color-darkest);
}
```

Si el proyecto usa Tailwind, también conviene extender el archivo `tailwind.config.js`.

```js
theme: {
  extend: {
    colors: {
      brand: {
        darkest: '#051F20',
        dark: '#0B2B26',
        mediumDark: '#163832',
        medium: '#235347',
        light: '#8EB69B',
        lightest: '#DAF1DE',
      },
    },
  },
}
```

Así se podrían usar clases como:

```html
<button class="bg-brand-medium hover:bg-brand-mediumDark text-white">
  Guardar
</button>
```

Esto es mejor que repetir códigos hexadecimales directamente en cada componente.

---

## 7. Qué partes del sistema debe impactar

Esta historia impacta principalmente el frontend.

No debería tocar:

- Modelos.
- Migraciones.
- Controladores.
- Base de datos.
- Lógica de pedidos.
- Lógica de inventario.
- Permisos.
- Autenticación.
- Endpoints.
- Servicios internos.

Sí debería revisar:

- CSS global.
- Tailwind config.
- Layout del cliente.
- Layout del administrador.
- Header admin.
- Sidebar admin.
- Navbar cliente.
- Botones reutilizables.
- Links.
- Cards.
- Formularios.
- Tablas.
- Badges.
- Estados activos.
- Estados hover.
- Modales.
- Alerts.
- Elementos del catálogo.

---

## 8. Qué no se debe hacer

Esta historia no debe convertirse en una reestructuración completa del sistema.

No se debe:

- Cambiar lógica de negocio.
- Modificar la base de datos.
- Eliminar componentes sin justificación.
- Cambiar rutas.
- Cambiar permisos.
- Cambiar comportamiento de botones.
- Cambiar validaciones.
- Modificar endpoints.
- Rehacer pantallas completas si no es necesario.
- Introducir una librería nueva solo para cambiar colores.
- Crear estilos duplicados.
- Dejar colores hardcodeados sin necesidad.

La tarea debe enfocarse en estandarización visual.

---

## 9. Criterios de aceptación explicados

### CA-01 — Variables CSS definidas en el archivo raíz con los seis colores

Debe existir una definición global de la paleta.

Resultado esperado:

```css
--color-darkest: #051F20;
--color-dark: #0B2B26;
--color-medium-dark: #163832;
--color-medium: #235347;
--color-light: #8EB69B;
--color-lightest: #DAF1DE;
```

Esto garantiza que los colores sean reutilizables y mantenibles.

---

### CA-02 — Header del admin usa `--color-dark`

El encabezado del panel administrativo debe usar el color:

```css
#0B2B26
```

Esto le da una apariencia más seria y profesional al área interna del sistema.

---

### CA-03 — Botones primarios usan `--color-medium`

Los botones principales deben usar:

```css
#235347
```

Esto aplica a acciones como:

- Guardar.
- Crear.
- Confirmar.
- Actualizar.
- Añadir al carrito.
- Iniciar sesión.

El hover puede usar:

```css
#163832
```

---

### CA-04 — Sidebar admin usa `--color-darkest` o `--color-dark`

El menú lateral del administrador debe usar uno de estos dos colores:

```css
#051F20
#0B2B26
```

La elección depende de cuál se integre mejor con el diseño actual.

Si el header usa `#0B2B26`, el sidebar puede usar `#051F20` para crear contraste.

---

### CA-05 — Fondo de tienda cliente usa `--color-lightest`

La tienda pública debe usar:

```css
#DAF1DE
```

como fondo suave.

Esto no significa que todos los componentes deban ser verdes. Las cards pueden seguir siendo blancas o muy claras para mantener buena legibilidad.

---

### CA-06 — Links activos y hover usan `--color-light`

Los enlaces activos y estados hover deben usar:

```css
#8EB69B
```

Esto aplica a:

- Menú activo.
- Categoría activa.
- Hover en navegación.
- Links secundarios.
- Elementos seleccionados.

---

### CA-07 — Contraste accesible WCAG AA

El texto debe ser legible.

Combinaciones recomendadas:

```css
Texto blanco sobre #051F20
Texto blanco sobre #0B2B26
Texto blanco sobre #235347
Texto oscuro sobre #DAF1DE
Texto oscuro sobre #8EB69B
```

Combinaciones que deben evitarse:

```css
Texto #8EB69B sobre #DAF1DE
Texto blanco sobre #DAF1DE
Texto #DAF1DE sobre blanco
```

El objetivo es que el usuario pueda leer sin esfuerzo.

---

### CA-08 — La paleta reemplaza colores hardcodeados anteriores

Se deben buscar colores viejos o clases antiguas.

Ejemplos de posibles residuos:

```css
#22c55e
#16a34a
#14532d
green
emerald
lime
```

Si existen colores antiguos, deben reemplazarse por variables o clases de la nueva paleta, salvo que sean colores semánticos justificados como error, advertencia o éxito.

---

### CA-09 — El build de Tailwind/Vite pasa sin errores

Después de aplicar los cambios debe ejecutarse:

```bash
npm run build
```

Y el proyecto debe compilar sin errores.

También se recomienda ejecutar:

```bash
npm run dev
```

para revisar visualmente el sistema.

---

## 10. Diseño UX/UI recomendado

### Cliente

La interfaz del cliente debe sentirse más clara, amigable y comercial.

Propuesta visual:

```text
Fondo general: #DAF1DE
Texto principal: #051F20
Cards de productos: blanco o tonos muy claros
Botones primarios: #235347
Hover de botones: #163832
Bordes suaves: #8EB69B
Links activos: #8EB69B
Navbar: #051F20 o #0B2B26
```

El catálogo debe mantener una apariencia limpia. No conviene saturar todo con verde oscuro, porque el cliente necesita una experiencia ligera y fácil de explorar.

Las cards de producto pueden tener:

- Fondo blanco.
- Bordes suaves.
- Sombra ligera.
- Botón primario verde.
- Texto oscuro.
- Badge claro para disponibilidad.

Ejemplo visual esperado:

```text
Página cliente:
- Fondo verde muy claro.
- Cards limpias.
- Botones verdes oscuros.
- Hover más oscuro.
- Texto legible.
- Links con acento verde claro.
```

---

### Administrador

La interfaz administrativa puede usar tonos más oscuros porque representa una herramienta interna.

Propuesta visual:

```text
Sidebar: #051F20
Header: #0B2B26
Hover menú: #163832
Botones primarios: #235347
Bordes/acento: #8EB69B
Fondos suaves de contenido: #DAF1DE o blanco
Texto sobre oscuro: blanco
Texto sobre claro: #051F20
```

El admin debe sentirse:

- Ordenado.
- Profesional.
- Sólido.
- Fácil de leer.
- Con jerarquía visual clara.

---

## 11. Plan técnico de implementación

### Paso 1 — Revisar estructura actual del frontend

Antes de modificar código, se debe identificar:

- Dónde está el CSS global.
- Dónde está el archivo de Tailwind.
- Qué layout usa el cliente.
- Qué layout usa el admin.
- Qué componentes de botones existen.
- Qué componentes de navegación existen.
- Si hay clases hardcodeadas.
- Si hay colores inline.
- Si hay estilos duplicados.

Archivos probables a revisar:

```text
src/
resources/
app/
components/
layouts/
pages/
tailwind.config.js
vite.config.js
app.css
index.css
global.css
```

La ruta exacta depende de la estructura real del proyecto.

---

### Paso 2 — Definir variables globales

Agregar en el archivo CSS raíz:

```css
:root {
  --color-darkest: #051F20;
  --color-dark: #0B2B26;
  --color-medium-dark: #163832;
  --color-medium: #235347;
  --color-light: #8EB69B;
  --color-lightest: #DAF1DE;
}
```

Esto cumple el primer criterio de aceptación.

---

### Paso 3 — Integrar colores en Tailwind

Si el proyecto usa Tailwind, agregar:

```js
colors: {
  brand: {
    darkest: '#051F20',
    dark: '#0B2B26',
    mediumDark: '#163832',
    medium: '#235347',
    light: '#8EB69B',
    lightest: '#DAF1DE',
  }
}
```

Esto permite usar clases como:

```html
bg-brand-dark
bg-brand-medium
hover:bg-brand-mediumDark
text-brand-darkest
border-brand-light
```

---

### Paso 4 — Actualizar layout del admin

Modificar el header administrativo para que use:

```css
background-color: var(--color-dark);
```

o en Tailwind:

```html
<header class="bg-brand-dark text-white">
```

Modificar el sidebar para usar:

```html
<aside class="bg-brand-darkest text-white">
```

o:

```html
<aside class="bg-brand-dark text-white">
```

El hover del menú debería usar:

```html
hover:bg-brand-mediumDark
```

El link activo podría usar:

```html
bg-brand-mediumDark text-brand-light
```

---

### Paso 5 — Actualizar botones primarios

Identificar todos los botones principales y cambiar su estilo a:

```html
class="bg-brand-medium hover:bg-brand-mediumDark text-white"
```

O en CSS:

```css
.btn-primary {
  background-color: var(--color-medium);
  color: white;
}

.btn-primary:hover {
  background-color: var(--color-medium-dark);
}
```

---

### Paso 6 — Actualizar tienda cliente

El layout del cliente debe usar el fondo suave:

```html
<main class="bg-brand-lightest">
```

o:

```css
.client-layout {
  background-color: var(--color-lightest);
}
```

Las cards deben mantener contraste:

```html
<div class="bg-white text-brand-darkest border border-brand-light">
```

Botones del cliente:

```html
<button class="bg-brand-medium hover:bg-brand-mediumDark text-white">
  Añadir al carrito
</button>
```

---

### Paso 7 — Actualizar links y hover

Los links activos y hover deben usar:

```html
class="hover:text-brand-light"
```

Para link activo:

```html
class="text-brand-light"
```

Si el fondo es claro, conviene usar un color más oscuro para texto normal y reservar `#8EB69B` para bordes o detalles, porque puede perder contraste.

---

### Paso 8 — Reemplazar colores hardcodeados

Buscar colores viejos con comandos como:

```bash
grep -R "#22c55e\|#16a34a\|#14532d\|emerald\|lime\|green" src resources -n
```

También se puede buscar:

```bash
grep -R "bg-green\|text-green\|border-green\|bg-emerald\|text-emerald\|border-emerald" src resources -n
```

Cada color viejo debe ser evaluado y reemplazado por la nueva paleta.

---

### Paso 9 — Validar contraste

Revisar visualmente y, si es posible, con herramienta de contraste:

- Texto blanco sobre `#235347`.
- Texto blanco sobre `#0B2B26`.
- Texto blanco sobre `#051F20`.
- Texto oscuro sobre `#DAF1DE`.
- Links sobre fondos oscuros.
- Links sobre fondos claros.
- Botones deshabilitados.
- Badges.

---

### Paso 10 — Ejecutar build

Ejecutar:

```bash
npm run build
```

Si falla, revisar:

- Sintaxis de Tailwind.
- Configuración de colores.
- Clases mal escritas.
- Variables CSS mal definidas.
- Archivos importados incorrectamente.

---

## 12. Casos de prueba

### CP-01 — Verificar contraste de texto sobre botones primarios

**Objetivo:**  
Validar que los botones primarios sean legibles con la nueva paleta.

**Precondiciones:**  
La paleta ya está aplicada en los botones principales.

**Pasos:**

1. Abrir la tienda cliente.
2. Identificar un botón principal como "Añadir al carrito".
3. Verificar que el fondo use `#235347`.
4. Verificar que el texto sea blanco o tenga contraste suficiente.
5. Pasar el mouse sobre el botón.
6. Verificar que el hover use `#163832`.

**Resultado esperado:**  
El botón se lee correctamente y el hover es visible.

**Comentarios:**  
Debe cumplir contraste accesible WCAG AA.

---

### CP-02 — Verificar header del administrador

**Objetivo:**  
Confirmar que el header del panel administrativo usa la nueva paleta.

**Precondiciones:**  
El usuario debe tener acceso al panel administrativo.

**Pasos:**

1. Iniciar sesión como administrador.
2. Entrar al panel administrativo.
3. Observar el header superior.
4. Verificar que use `#0B2B26`.
5. Revisar que el texto sea blanco o claro.
6. Verificar que los íconos y links sean legibles.

**Resultado esperado:**  
El header del admin usa `--color-dark` y mantiene buena legibilidad.

**Comentarios:**  
No debe verse mezclado con colores anteriores.

---

### CP-03 — Verificar sidebar del administrador

**Objetivo:**  
Validar que el sidebar admin use la nueva paleta y tenga estados activos claros.

**Precondiciones:**  
El usuario debe estar autenticado como administrador.

**Pasos:**

1. Entrar al panel administrativo.
2. Observar el sidebar.
3. Verificar que use `#051F20` o `#0B2B26`.
4. Pasar el mouse sobre varias opciones.
5. Verificar que el hover use `#163832`.
6. Seleccionar una opción del menú.
7. Verificar que el estado activo sea claro.

**Resultado esperado:**  
El sidebar se ve integrado, oscuro, legible y con estados hover/activo visibles.

**Comentarios:**  
El menú no debe parecer flotante ni separado del diseño.

---

### CP-04 — Verificar tienda cliente

**Objetivo:**  
Confirmar que la tienda cliente usa el fondo suave y los botones de la nueva paleta.

**Precondiciones:**  
La tienda cliente debe estar disponible.

**Pasos:**

1. Abrir la página principal o catálogo.
2. Verificar que el fondo general use `#DAF1DE`.
3. Revisar las cards de productos.
4. Confirmar que los botones principales usan `#235347`.
5. Revisar links y hover.
6. Probar navegación entre secciones.

**Resultado esperado:**  
La tienda cliente mantiene una apariencia clara, coherente y moderna.

**Comentarios:**  
Las cards deben seguir siendo legibles y no perder contraste contra el fondo.

---

### CP-05 — Verificar colores hardcodeados residuales

**Objetivo:**  
Detectar si quedaron colores viejos en el código.

**Precondiciones:**  
Los cambios visuales ya fueron implementados.

**Pasos:**

1. Abrir terminal en la raíz del proyecto.
2. Ejecutar búsqueda de colores viejos.
3. Revisar resultados.
4. Reemplazar colores no justificados.
5. Ejecutar build.

Comando sugerido:

```bash
grep -R "#22c55e\|#16a34a\|#14532d\|emerald\|lime\|green" src resources -n
```

**Resultado esperado:**  
No quedan colores viejos en componentes principales, salvo casos justificados.

**Comentarios:**  
Si existen colores semánticos para éxito, error o advertencia, deben revisarse antes de reemplazarlos.

---

## 13. Riesgos técnicos

### Riesgo 1 — Romper la apariencia de pantallas existentes

Al cambiar clases globales o variables, varias pantallas pueden verse afectadas.

**Mitigación:**  
Aplicar cambios graduales y revisar cliente y admin por separado.

---

### Riesgo 2 — Bajo contraste

Algunas combinaciones de verdes claros pueden no ser legibles.

**Mitigación:**  
Usar texto blanco sobre fondos oscuros y texto oscuro sobre fondos claros. Validar contraste en botones, links y badges.

---

### Riesgo 3 — Colores viejos mezclados con la nueva paleta

Pueden quedar clases antiguas como `green`, `emerald` o códigos hexadecimales previos.

**Mitigación:**  
Buscar colores hardcodeados con `grep` o búsqueda global del editor.

---

### Riesgo 4 — Inconsistencia entre admin y cliente

Puede que el admin quede bien, pero la tienda cliente no, o viceversa.

**Mitigación:**  
Definir reglas visuales para cada área usando la misma paleta.

---

### Riesgo 5 — Problemas en mobile

Algunos colores pueden estar definidos solo para desktop o en clases condicionales.

**Mitigación:**  
Probar en desktop, tablet y mobile.

---

### Riesgo 6 — Cambiar más de lo necesario

La historia es visual, pero se podría tocar lógica innecesariamente.

**Mitigación:**  
Limitar cambios a estilos, layouts y componentes visuales.

---

## 14. Checklist antes de programar

Antes de implementar:

- [ ] Revisar estructura del proyecto.
- [ ] Ubicar CSS global.
- [ ] Ubicar configuración de Tailwind.
- [ ] Identificar layout del cliente.
- [ ] Identificar layout del admin.
- [ ] Identificar componente de botones.
- [ ] Identificar navbar.
- [ ] Identificar sidebar.
- [ ] Buscar colores hardcodeados actuales.
- [ ] Confirmar si se usarán variables CSS, Tailwind o ambos.
- [ ] Revisar pantallas principales antes de cambiar.
- [ ] Tomar capturas si es necesario para comparar antes/después.

---

## 15. Checklist después de programar

Después de implementar:

- [ ] Variables CSS creadas.
- [ ] Tailwind actualizado si aplica.
- [ ] Header admin actualizado.
- [ ] Sidebar admin actualizado.
- [ ] Botones primarios actualizados.
- [ ] Fondo cliente actualizado.
- [ ] Links activos y hover actualizados.
- [ ] Cards revisadas.
- [ ] Tablas revisadas.
- [ ] Formularios revisados.
- [ ] Modales revisados.
- [ ] Badges revisados.
- [ ] Colores viejos eliminados o justificados.
- [ ] Contraste revisado.
- [ ] Responsive revisado.
- [ ] `npm run build` ejecutado sin errores.
- [ ] `npm run dev` revisado visualmente.

---

## 16. Comandos útiles

Instalar dependencias si hace falta:

```bash
npm install
```

Levantar entorno de desarrollo:

```bash
npm run dev
```

Compilar:

```bash
npm run build
```

Buscar colores viejos:

```bash
grep -R "#22c55e\|#16a34a\|#14532d\|emerald\|lime\|green" src resources -n
```

Buscar clases Tailwind verdes anteriores:

```bash
grep -R "bg-green\|text-green\|border-green\|bg-emerald\|text-emerald\|border-emerald" src resources -n
```

---

## 17. Plan de commits recomendado

Los commits deben ser pequeños y claros.

```text
CF4-132: analizar estructura visual actual
CF4-132: definir variables globales de paleta verde
CF4-132: integrar paleta en configuración de Tailwind
CF4-132: aplicar paleta en layout administrativo
CF4-132: aplicar paleta en tienda cliente
CF4-132: actualizar botones y estados hover
CF4-132: reemplazar colores hardcodeados antiguos
CF4-132: ajustar contraste y responsive
CF4-132: validar build y pruebas visuales
```

---

## 18. Definition of Done

La historia puede considerarse terminada cuando:

- Los seis colores están definidos globalmente.
- La paleta está integrada en Tailwind si el proyecto lo utiliza.
- El header del admin usa `#0B2B26`.
- El sidebar admin usa `#051F20` o `#0B2B26`.
- Los botones primarios usan `#235347`.
- Los hover principales usan `#163832`.
- Los links activos usan `#8EB69B`.
- El fondo de la tienda cliente usa `#DAF1DE`.
- No quedan colores anteriores sin justificación.
- El sistema mantiene contraste accesible.
- La interfaz se ve coherente en cliente y admin.
- El proyecto compila correctamente con `npm run build`.
- Se revisó responsive en mobile y desktop.

---

## 19. Explicación final de la historia

Esta historia no debe verse como un simple cambio de colores. En realidad, es una tarea de estandarización visual y de identidad de producto.

Su propósito es que el sistema Ciclo Finca 4 / Ciclo Pérez Catálogo tenga una imagen más profesional, coherente y fácil de mantener.

El cambio debe hacerse con cuidado porque afecta muchas pantallas. La mejor forma de implementarlo es centralizando los colores en variables CSS y, si aplica, en Tailwind. Luego se deben actualizar los componentes principales: admin, cliente, botones, links, sidebar, header, cards y estados visuales.

La historia estará correctamente terminada cuando el usuario pueda navegar por el cliente y el administrador sintiendo que ambas partes pertenecen al mismo sistema, sin colores antiguos mezclados, con buen contraste, buen responsive y una estética verde moderna basada en la paleta definida.
