**Español** | [English](../README.md) | [Português Brasileiro](README-pt_BR.md)

# FGV OpenRank

[![OJS compatibility](https://img.shields.io/badge/ojs-3.5.0.x-brightgreen)](https://github.com/pkp/ojs/tree/stable-3_5_0)
[![License type](https://img.shields.io/badge/license-GPL--3.0-blue)](https://www.gnu.org/licenses/gpl-3.0)

Este módulo añade un bloque de ranking a la página de inicio de revistas que usan [OJS](https://pkp.sfu.ca/software/ojs/). El bloque presenta artículos en pestañas — **Más recientes**, **Más leídos**, **Más citados**, **En tendencia** y una pestaña de contenido libre, **Destacado** — y el gestor de la revista decide qué pestañas aparecen, en qué orden, con qué título, descripción y cantidad de elementos.

La navegación de OJS está organizada por fascículo, lo que obliga a la persona lectora a recorrer ediciones hasta encontrar lo que le interesa. El bloque añade a eso una vía de descubrimiento a nivel de artículo, construida a partir de los metadatos y las estadísticas de acceso de la propia revista y de datos de citación (Crossref) y de atención en línea (Altmetric).

## Cómo funciona

Usted elige dónde queda el bloque en los ajustes del módulo — al principio de la página de inicio, después de una sección determinada de ella, al final, o en el lugar donde inserte `<div class="rankingTabs"></div>` en el Contenido Adicional. Cada pestaña carga sus artículos de forma asíncrona desde la API del propio módulo, que entrega datos de una caché actualizada una vez al día. Los servicios externos (Crossref, Altmetric) son consultados por la tarea programada, no mientras la persona lectora espera la página.

| Pestaña | Qué muestra | Fuente | Requiere |
| --- | --- | --- | --- |
| **Más recientes** | Los artículos publicados más recientemente | OJS | — |
| **Más leídos** | Los artículos más vistos en los últimos *N* días (120 por defecto) | Estadísticas de uso de OJS | Estadísticas de uso registradas |
| **Más citados** | Los artículos más citados de la revista | [Crossref](https://www.crossref.org/services/cited-by/) | ISSN de la revista + DOI de los artículos |
| **En tendencia** | Los artículos con mayor puntuación Altmetric | API de [Altmetric](https://www.altmetric.com/) o lista manual de DOI | DOI de los artículos (ISSN y clave de API si es automático) |
| **Destacado** | El contenido que usted mismo escriba (texto con formato) | — | — |

## Primeros pasos

Con el módulo habilitado, la acción **Guía de configuración**, en su fila de la lista de módulos, abre un paso a paso dentro del propio OJS, con un enlace directo a cada pantalla. Recorre las seis cosas que el bloque necesita en el panel: la posición en la página de inicio, las pestañas, el ISSN de la revista, los DOI de los artículos, la pestaña Tendencias y la actualización diaria de la caché. Lo que se hace en el servidor — `allowed_hosts` y, para los donuts de la pestaña Tendencias, los dominios de Altmetric — no tiene pantalla que enlazar, así que la guía solo lo menciona y son las secciones siguientes las que lo explican.

### 1. Instale el módulo

Descargue el `.tar.gz` de la última versión compatible con su OJS desde la [página de versiones](https://github.com/lepidus/FGV-OpenRank/releases), vaya a *Ajustes → Sitio web → Módulos → Subir un nuevo módulo*, envíe el archivo y habilite el módulo en su revista.

### 2. Elija dónde aparece el bloque

Abra los *Ajustes* del módulo y elija la **Posición en la página de inicio de la revista**:

- **Al principio de la página de inicio** — por encima de todas las demás secciones.
- **Después de una sección determinada de la página de inicio** — luego responda **¿Después de qué sección?**: `1` coloca el bloque después de la primera sección, `2` después de la segunda, y así sucesivamente. El conteo considera cada sección que el tema apila en la página de inicio — la imagen de la página de inicio, la descripción de la revista, los avisos, el último número, el contenido adicional y todo lo demás que el tema muestre. Lo que cuenta como sección depende, por lo tanto, del tema y de lo que la revista haya configurado, y algunos temas agrupan varias de ellas dentro de un mismo contenedor, así que puede que deba probar algunos números. Un número mayor que la cantidad de secciones lleva el bloque al final de la página.
- **Al final de la página de inicio** — por debajo de todas las demás secciones.
- **Donde esté el elemento `rankingTabs`** (opción por defecto) — en *Ajustes → Sitio web → Apariencia → Avanzado*, agregue lo siguiente en **Contenido Adicional**:

  ```html
  <div class="rankingTabs"></div>
  ```

  El bloque se muestra dentro de ese elemento.

Las tres primeras opciones las resuelve el propio módulo, sin CSS personalizado de por medio. Se cuentan entre los bloques de la página de inicio en lugar de emparejarse con clases CSS propias de cada tema, así que se sostienen cuando un tema renombra, reordena o elimina alguna sección — un tema muy personalizado todavía puede requerir ajustes.

### 3. Autorice el host

Las pestañas consultan la API del módulo en la propia dirección de la revista, por lo que el host debe estar en `allowed_hosts`, en el archivo `config.inc.php`:

```php
allowed_hosts = '["mirevista.org"]'
```

### 4. Autorice los dominios de Altmetric, si el servidor envía una CSP

Este paso solo concierne a la pestaña **En tendencia** y solo cuando el servidor web añade cabeceras personalizadas con una *Content Security Policy*.

| Directiva | Dominios | Qué cubre |
| --- | --- | --- |
| `script-src` | `https://d1bxh8uas1mnw7.cloudfront.net`<br>`https://embed.altmetric.com`<br>`https://api.altmetric.com` | el script de embed que inserta el módulo, el script del medidor que este carga a continuación y la puntuación misma — que viaja como JSONP y por eso el navegador la verifica como un script |
| `style-src` | `https://embed.altmetric.com` | la hoja de estilos del medidor |
| `img-src` | `https://badges.altmetric.com` | la imagen del medidor |

Un ejemplo mínimo para nginx:

```nginx
add_header Content-Security-Policy "
    script-src 'self' 'unsafe-inline' 'unsafe-eval'
        https://d1bxh8uas1mnw7.cloudfront.net
        https://embed.altmetric.com
        https://api.altmetric.com;
    style-src 'self' 'unsafe-inline'
        https://embed.altmetric.com;
    img-src 'self' data:
        https://badges.altmetric.com;
" always;
```

O bien, si la política se declara en una etiqueta `<meta>` del tema:

```html
<meta http-equiv="Content-Security-Policy"
      content="script-src 'self' 'unsafe-inline' 'unsafe-eval' https://d1bxh8uas1mnw7.cloudfront.net https://embed.altmetric.com https://api.altmetric.com;
               style-src 'self' 'unsafe-inline' https://embed.altmetric.com;
               img-src 'self' data: https://badges.altmetric.com;">
```

> [!NOTE]
> Son ejemplos, no una política completa: incorpore los dominios a las directivas que ya tenga, conservando lo que OJS y su tema necesitan.

### 5. Configure las pestañas

Abra los *Ajustes* del módulo. La tabla **Pestañas** muestra las cinco pestañas: marque **Habilitado** para mostrar u ocultar cada una, use las flechas para cambiar el orden en que aparecen y haga clic en **Editar** para configurar una pestaña. Los cambios en la tabla se guardan al instante.

## Configuración de cada pestaña

Todas las pestañas tienen:

- **Título personalizado** — sustituye al título predeterminado. Multilingüe.
- **Descripción** — el texto que se muestra sobre la lista. Multilingüe.
- **Elementos por pestaña** — cuántos artículos reúne la pestaña (4 por defecto).
- **Elementos por página** — cuántos se muestran a la vez, paginando el resto (4 por defecto).

Algunas pestañas tienen ajustes propios:

- **Más leídos:** *Días para más leídos* — el período usado para contar las visitas (120 por defecto).
- **Destacado:** *Contenido personalizado* — un campo de texto con formato. Esta pestaña no hace llamadas a ninguna API; muestra exactamente lo que usted escriba.
- **En tendencia:** la clave de API de Altmetric y la lista manual de DOI, descritas a continuación.

### La pestaña En tendencia: clave de API o lista manual

La pestaña funciona de dos maneras:

- **Con una clave de API de Altmetric.** Los artículos se obtienen de la API de Altmetric por el ISSN de la revista y se ordenan por puntuación. La clave se valida al guardarla y se almacena cifrada con el `app_key` de `config.inc.php`, que toda instalación de OJS 3.5 ya tiene.
- **Con una lista manual de DOI.** Sin clave almacenada, la pestaña muestra los DOI que usted registre, en el orden en que fueron agregados. Solo se aceptan DOI de artículos publicados en esta revista.

> [!NOTE]
> Mientras haya una clave almacenada, la lista manual se ignora. Para volver a usarla, marque *Eliminar la clave de API almacenada* y guarde.

## Caché y actualización diaria

Las pestañas se sirven desde una caché por revista, guardada en la caché de OJS. Una tarea programada, *Actualización de caché de FGV OpenRank*, actualiza todas las pestañas de todas las revistas habilitadas diariamente a medianoche. OJS 3.5 ejecuta las tareas programadas por sí mismo al final de las solicitudes web mientras `task_runner` esté en `On` en la sección `[schedule]` de `config.inc.php` (el valor predeterminado); los sitios con mucho tráfico deben desactivarlo y ejecutar `php lib/pkp/tools/scheduler.php run` cada minuto desde el crontab del servidor. Si la caché está vacía cuando alguien visita la página, los datos se obtienen en ese momento.

Para actualizar manualmente, desde la raíz de OJS:

```bash
php lib/pkp/tools/scheduler.php test --name='APP\plugins\generic\rankingPlugin\classes\tasks\RankingCacheUpdateTask'
```

## Requisitos

- **OJS 3.5.0.x**, a partir de 3.5.0-1.
- **`allowed_hosts`** con el host de la revista.
- **Un ISSN** registrado en la revista — necesario para *Más citados* y para *En tendencia* cuando se usa una clave de API.
- **DOI** asignados a los artículos — *Más citados* y *En tendencia* identifican los artículos por su DOI, por lo que un artículo sin DOI nunca aparece en ellas.
- **Dominios de Altmetric autorizados en la CSP**, solo si el servidor web envía una *Content Security Policy* personalizada y la pestaña *En tendencia* está en uso.

## Solución de problemas

<details>
<summary><strong>El bloque no aparece en la página de inicio</strong></summary>

Verifique que el módulo esté habilitado en esta revista. Si la posición está configurada como *Donde esté el elemento `rankingTabs`*, confirme que `<div class="rankingTabs"></div>` esté en el *Contenido Adicional*. Solo se utiliza la primera aparición del elemento en la página.

</details>

<details>
<summary><strong>El bloque aparece en el lugar equivocado</strong></summary>

Con *Después de una sección determinada de la página de inicio*, pruebe otra respuesta para **¿Después de qué sección?** — cuántas secciones tiene la página de inicio depende del tema y de lo que la revista haya configurado. Números demasiado altos llevan el bloque al final de la página.

</details>

<details>
<summary><strong>Una pestaña muestra un mensaje de error</strong></summary>

Confirme que el host de la revista esté en `allowed_hosts`. Los errores provenientes de Crossref o Altmetric quedan registrados en los logs del servidor de OJS.

</details>

<details>
<summary><strong>Más citados o En tendencia está vacía</strong></summary>

Por lo general, la revista no tiene ISSN, los artículos no tienen DOI o el servicio externo aún no tiene datos sobre ellos. En la pestaña En tendencia sin clave de API, verifique que la lista manual de DOI esté completa.

</details>

<details>
<summary><strong>En tendencia muestra los artículos, pero los medidores aparecen con un signo de interrogación gris</strong></summary>

La lista proviene de la caché del módulo, mientras que el medidor lo obtiene el navegador del lector directamente de Altmetric, así que uno puede fallar sin el otro. Si su servidor envía una *Content Security Policy*, compruebe que los dominios de Altmetric estén autorizados. La consola del navegador indica tanto la petición bloqueada como la directiva que la bloqueó.

</details>

<details>
<summary><strong>Más leídos está vacía</strong></summary>

No hay visitas registradas en el período. Aumente el valor de *Días para más leídos* o verifique que se estén recopilando las estadísticas de uso de OJS.

</details>

<details>
<summary><strong>La clave de Altmetric fue rechazada al guardar</strong></summary>

La clave no es válida para la API de Altmetric. El módulo la verifica en la API antes de almacenarla, así que una clave rechazada por la API nunca se guarda.

</details>

## Actualización desde la versión para OJS 3.3

- **Clave de API de Altmetric.** La versión 3.3 la cifraba con `api_key_secret`, que OJS 3.5 ya no usa. La actualización la vuelve a cifrar con el `app_key` de OJS siempre que `api_key_secret` siga en `config.inc.php`; de lo contrario, la clave se elimina y debe introducirse de nuevo, y hasta entonces la pestaña Tendencias usa la lista manual de DOI.
- En OJS 3.5 los DOI dejaron de ser un módulo: se configuran en *Ajustes → Distribución → DOI*.

## Desarrollo

La pantalla de ajustes es un componente Vue compilado con Vite en `public/build`, que se versiona para que el paquete de publicación funcione sin paso de compilación. Después de cambiar algo en `resources/js`, ejecute en el directorio del módulo:

```bash
npm install
npm run build
```

Las pruebas unitarias se ejecutan desde la raíz de OJS:

```bash
php lib/pkp/lib/vendor/bin/phpunit --configuration lib/pkp/tests/phpunit.xml plugins/generic/rankingPlugin/tests
```

## Créditos

Este módulo fue ideado y financiado por la [Fundação Getulio Vargas (FGV)](https://periodicos.fgv.br/index) y desarrollado por [Lepidus Tecnologia](https://lepidus.com.br/). Está en producción en el [Portal de Periódicos de la FGV](https://periodicos.fgv.br/index).

## Licencia

Este módulo está licenciado bajo la [Licencia Pública General GNU v3.0](https://www.gnu.org/licenses/gpl-3.0).

Copyright (c) 2025-2026 Lepidus Tecnologia.

Copyright (c) 2025-2026 Fundação Getulio Vargas.
