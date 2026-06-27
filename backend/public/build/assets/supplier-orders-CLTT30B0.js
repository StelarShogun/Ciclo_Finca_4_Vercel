import"./ajax-pagination-DQmoQw6x.js";import{a as H,g as I,f as k,h as A,i as N,b as T,c as g,e as P}from"./swal-BtavKoil.js";import"./legacy-dom-UsBvu_52.js";import"./preload-helper-I4rgV-VL.js";function b(){return document.querySelector('meta[name="csrf-token"]')?.getAttribute("content")??""}document.addEventListener("DOMContentLoaded",()=>{const r=document.getElementById("supplier-orders-filters-form"),a=document.getElementById("date_from"),t=document.getElementById("date_to");a&&t&&(a.addEventListener("change",()=>{t.min=a.value||"",t.value&&t.value<a.value&&(t.value=a.value)}),a.value&&(t.min=a.value)),r&&r.addEventListener("submit",n=>{const e=a?.value,s=t?.value;e&&s&&s<e&&(n.preventDefault(),H('La fecha "Hasta" no puede ser anterior a la fecha "Desde".',"Rango de fechas inválido"))})});function j(){document.getElementById("view-order-modal")?.classList.remove("active"),f=null}function D(){document.getElementById("view-supplier-modal")?.classList.remove("active")}const w={draft:"Borrador",pending:"Pendiente",confirmed:"Confirmado",partial_received:"Recepción parcial",delivered:"Entregado",cancelled:"Cancelado"};let f=null;function L(r){const a=String(r),t=document.querySelector(`tr[data-order-id="${a}"]`);if(t)return t.getAttribute("data-order-state");const n=document.querySelector(`.cf4-supplier-orders-module[data-supplier-order-num="${a}"]`);return n?n.getAttribute("data-supplier-order-state"):null}function z(r,a){const t=String(r),n=document.querySelector(`tr[data-order-id="${t}"]`);n&&n.setAttribute("data-order-state",a);const e=document.querySelector(`.cf4-supplier-orders-module[data-supplier-order-num="${t}"]`);e&&e.setAttribute("data-supplier-order-state",a)}function _(r,a,t="icon"){const n=(s,o,l,d,v)=>t==="text"?`<button type="button" class="btn ${s}" onclick="${v}('${r}')" title="${o}">
                <i class="fas ${l}"></i> ${d}
            </button>`:`<button class="action-btn ${s}" type="button" onclick="${v}('${r}')" title="${o}">
            <i class="fas ${l}"></i>
        </button>`,e=t==="icon"?`<button class="action-btn secondary" type="button" onclick="viewOrder('${r}')" title="Ver detalles"><i class="fas fa-eye"></i></button>`:"";if(a==="draft")return`${e}${n(t==="icon"?"success":"btn-primary","Confirmar pedido","fa-check","Confirmar","confirmOrder")}${n(t==="icon"?"danger":"btn-secondary","Cancelar pedido","fa-times","Cancelar","cancelOrder")}`;if(a==="pending")return`${e}${n(t==="icon"?"success":"btn-primary","Confirmar pedido","fa-check","Confirmar","confirmOrder")}${n(t==="icon"?"danger":"btn-secondary","Cancelar","fa-times","Cancelar","cancelOrder")}`;if(a==="confirmed"){const s=`/supplier-orders/${r}/detail`;return t==="text"?`${e}
                <a class="btn btn-primary" href="${s}" title="Registrar recepción de mercancía">
                    <i class="fas fa-clipboard-check"></i> Registrar recepción
                </a>
                ${n("btn-secondary","Cancelar pedido","fa-times","Cancelar","cancelOrder")}`:`${e}<a class="action-btn view" href="${s}" title="Registrar recepción"><i class="fas fa-clipboard-check"></i></a>${n("danger","Cancelar","fa-times","Cancelar","cancelOrder")}`}if(a==="partial_received"){const s=`/supplier-orders/${r}/detail`;return t==="text"?`${e}
                <a class="btn btn-primary" href="${s}" title="Completar recepción de mercancía">
                    <i class="fas fa-clipboard-check"></i> Completar recepción
                </a>
                <a class="btn btn-warning" href="${s}" title="Cerrar pedido con faltantes del proveedor">
                    <i class="fas fa-exclamation-triangle"></i> Cerrar con faltantes
                </a>
                ${n("btn-secondary","Cancelar pedido","fa-times","Cancelar","cancelOrder")}`:`${e}<a class="action-btn view" href="${s}" title="Completar recepción / cerrar con faltantes"><i class="fas fa-clipboard-check"></i></a>${n("danger","Cancelar","fa-times","Cancelar","cancelOrder")}`}return`${e}`}function B(r,a){z(r,a);const t=document.querySelector(`tr[data-order-id="${r}"]`);if(!t)return;const n=t.querySelector('[data-role="order-state-pill"]');n&&(n.className=`order-status-pill ${a}`,n.textContent=w[a]||a);const e=t.querySelector('[data-role="order-actions"]');e&&(e.innerHTML=_(r,a,"icon"))}function O(r){if(!f)return;const a=document.querySelector('#view-order-body [data-role="modal-state-badge"]');a&&(a.className=`status-badge ${r}`,a.textContent=w[r]||r)}function c(r){return String(r).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;")}function V(r){const a=document.getElementById("view-order-modal"),t=document.getElementById("view-order-body");!a||!t||(t.innerHTML=`
        <div class="loading-spinner" role="status">
            <i class="fas fa-spinner fa-spin fa-2x" aria-hidden="true"></i>
            <p>Cargando detalles…</p>
        </div>`,a.classList.add("active"),f=String(r),fetch(`/supplier-orders/${r}`,{headers:{"X-CSRF-TOKEN":b(),Accept:"application/json"}}).then(n=>n.json()).then(n=>{if(!n.success||!n.order){t.innerHTML='<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> No se pudieron cargar los detalles.</div>';return}const e=n.order,s=e.supplier?.name??"—",o=`/supplier-orders/${e.num_order}/detail`,l=(e.products||[]).map(i=>{const p=parseFloat(i.unit_price||0),u=parseFloat(i.total||0),m=i.received_quantity!==null&&i.received_quantity!==void 0?parseInt(i.received_quantity,10):0,C=i.received_quantity!==null&&i.received_quantity!==void 0?`<td class="text-center">${m}</td>`:"";return`
                <tr>
                    <td>${c(i.name||"N/A")}</td>
                    <td class="text-center">${i.quantity}</td>
                    ${C}
                    <td class="text-right">₡${p.toLocaleString("es-CR",{minimumFractionDigits:2})}</td>
                    <td class="text-right"><strong>₡${u.toLocaleString("es-CR",{minimumFractionDigits:2})}</strong></td>
                </tr>`}).join(""),d=e.products?.some(i=>i.received_quantity!==null&&i.received_quantity!==void 0),v=d?'<th class="text-center">Recibido</th>':"",M={draft:{label:"Borrador",icon:"fa-pencil-alt",color:"#64748b"},pending:{label:"Pendiente",icon:"fa-clock",color:"#f59e0b"},confirmed:{label:"Confirmado",icon:"fa-check",color:"#3b82f6"},partial_received:{label:"Recepción parcial",icon:"fa-clipboard-check",color:"#f97316"},delivered:{label:"Entregado",icon:"fa-truck",color:"#235347"},cancelled:{label:"Cancelado",icon:"fa-times",color:"#ef4444"}},S=(e.timeline||[]).map(i=>{const p=i.state==="delivered"&&(i.reason||"").startsWith("[Cierre con faltantes]"),u=p?{label:"Cerrado con faltantes",icon:"fa-exclamation-triangle",color:"#f59e0b"}:M[i.state]||{label:i.state,icon:"fa-circle",color:"#94a3b8"},m=p?i.reason.replace(/^\[Cierre con faltantes\]\s*/,""):i.reason,C=m?`<span class="tl-reason"><i class="fas fa-comment-alt"></i> ${c(m)}</span>`:"";return`
                <li class="tl-item">
                    <div class="tl-dot" style="background:${u.color};">
                        <i class="fas ${u.icon}"></i>
                    </div>
                    <div class="tl-body">
                        <span class="tl-state" style="color:${u.color};">${u.label}</span>
                        <span class="tl-meta">
                            <i class="fas fa-user-circle"></i> ${c(i.user_name)}
                            &nbsp;·&nbsp;
                            <i class="fas fa-calendar-alt"></i> ${i.changed_at}
                        </span>
                        ${C}
                    </div>
                </li>`}).join(""),h=(e.timeline||[]).find(i=>i.state==="confirmed"),F=h?`
                <div class="detail-section order-confirm-audit">
                    <h4><i class="fas fa-user-check"></i> Confirmación con proveedor</h4>
                    <div class="detail-grid">
                        <div class="detail-item"><label>Fecha:</label><span>${c(h.changed_at)}</span></div>
                        <div class="detail-item"><label>Registró:</label><span>${c(h.user_name||"—")}</span></div>
                    </div>
                </div>`:"",x=(e.products||[]).reduce((i,p)=>i+parseFloat(p.total||0),0),$=x>0?x:parseFloat(e.total||0),y=d?(e.products||[]).reduce((i,p)=>{const u=parseFloat(p.unit_price||0),m=parseInt(p.received_quantity??0,10)||0;return i+Math.round((u*m+Number.EPSILON)*100)/100},0):null,E=d&&y!==null?Math.max($-y,0):0,R=e.closed_with_shorts?`<div class="detail-item" style="color:#b45309;">
                   <label>Observación:</label>
                   <span><i class="fas fa-exclamation-triangle"></i> Cerrado con faltantes del proveedor</span>
               </div>`:"";t.innerHTML=`
            <div class="sale-details">
                <div class="detail-section">
                    <h4><i class="fas fa-info-circle"></i> Información general</h4>
                    <div class="detail-grid">
                        <div class="detail-item"><label>Nº Pedido:</label><span><strong>${c(e.po_number||"#"+e.num_order)}</strong></span></div>
                        <div class="detail-item"><label>Proveedor:</label><span>${c(s)}</span></div>
                        <div class="detail-item"><label>Fecha:</label><span>${e.date}</span></div>
                        <div class="detail-item"><label>Entrega estimada:</label><span>${e.estimated_delivery_date||"—"}</span></div>
                        ${e.received_at?`<div class="detail-item"><label>Fecha recepción:</label><span>${e.received_at}</span></div>`:""}
                        <div class="detail-item"><label>Estado:</label><span class="status-badge ${e.state}" data-role="modal-state-badge">${w[e.state]||e.state}</span></div>
                        ${R}
                    </div>
                    <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;" data-role="modal-actions">
                        ${_(e.num_order,e.state,"text")}
                        <a class="btn btn-secondary" href="${o}" title="Ver página de detalle">
                            <i class="fas fa-external-link-alt"></i> Ir a detalle
                        </a>
                    </div>
                </div>
                ${F}
                ${l?`
                <div class="detail-section">
                    <h4><i class="fas fa-box"></i> Productos pedidos</h4>
                    <table class="sale-products-table admin-table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th class="text-center">Pedido</th>
                                ${v}
                                <th class="text-right">Precio unit.</th>
                                <th class="text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>${l}</tbody>
                    </table>
                    <div class="sale-totals">
                        ${d&&E>.009?`
                            <div class="total-item">
                                <span><strong>Total pedido:</strong></span>
                                <span><strong>₡${$.toLocaleString("es-CR",{minimumFractionDigits:2})}</strong></span>
                            </div>
                            <div class="total-item">
                                <span><strong>Total recibido:</strong></span>
                                <span><strong>₡${y.toLocaleString("es-CR",{minimumFractionDigits:2})}</strong></span>
                            </div>
                            <div class="total-item total-final">
                                <span><strong>Faltante:</strong></span>
                                <span><strong>₡${E.toLocaleString("es-CR",{minimumFractionDigits:2})}</strong></span>
                            </div>
                        `:`
                            <div class="total-item total-final">
                                <span><strong>Total:</strong></span>
                                <span><strong>₡${$.toLocaleString("es-CR",{minimumFractionDigits:2})}</strong></span>
                            </div>
                        `}
                    </div>
                </div>`:""}
                ${S?`
                <div class="detail-section">
                    <h4><i class="fas fa-history"></i> Historial de estados</h4>
                    <ol class="order-timeline" style="margin-top:8px;">${S}</ol>
                </div>`:""}
            </div>`}).catch(()=>{t.innerHTML='<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Error de conexión al cargar los detalles.</div>'}))}function K(r){const a=document.getElementById("view-supplier-modal"),t=document.getElementById("view-supplier-body");!a||!t||(t.innerHTML=`
        <div class="loading-spinner" role="status">
            <i class="fas fa-spinner fa-spin fa-2x" aria-hidden="true"></i>
            <p>Cargando datos del proveedor…</p>
        </div>`,a.classList.add("active"),fetch(`/supplier/details/${r}`,{headers:{"X-CSRF-TOKEN":b(),Accept:"application/json"}}).then(n=>n.json()).then(n=>{if(!n.success||!n.supplier){t.innerHTML='<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> No se pudieron cargar los datos del proveedor.</div>';return}const e=n.supplier,s={active:"Activo",inactive:"Inactivo",suspended:"Suspendido"},o="★".repeat(Math.round(e.rating))+"☆".repeat(5-Math.round(e.rating));t.innerHTML=`
            <div class="sale-details">
                <div class="detail-section">
                    <h4><i class="fas fa-truck"></i> Datos del proveedor</h4>
                    <div class="detail-grid">
                        <div class="detail-item"><label>Nombre:</label><span><strong>${c(e.name)}</strong></span></div>
                        <div class="detail-item"><label>Contacto:</label><span>${c(e.primary_contact||"—")}</span></div>
                        <div class="detail-item"><label>Teléfono:</label><span>${c(e.phone||"—")}</span></div>
                        <div class="detail-item"><label>Correo:</label><span>${c(e.email||"—")}</span></div>
                        <div class="detail-item"><label>Dirección:</label><span>${c(e.address||"—")}</span></div>
                        <div class="detail-item"><label>Tiempo de entrega:</label><span>${e.delivery_time} día(s)</span></div>
                        <div class="detail-item"><label>Evaluación:</label><span title="${e.rating}/5">${o} (${e.rating})</span></div>
                        <div class="detail-item"><label>Estado:</label><span class="status-badge ${e.status}">${c(s[e.status]||e.status)}</span></div>
                        <div class="detail-item"><label>Productos activos:</label><span>${e.products_count}</span></div>
                    </div>
                </div>
            </div>`}).catch(()=>{t.innerHTML='<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Error de conexión.</div>'}))}async function q(r,a,t,n){if(!(await P({title:t.title,html:t.html,icon:t.icon,confirmButtonText:t.confirm,cancelButtonText:"Volver",danger:t.danger??!1,confirmStyle:t.confirmStyle??"primary"})).isConfirmed)return;const s=o=>{document.querySelectorAll(`tr[data-order-id="${r}"] .action-btn, #view-order-body [data-role="modal-actions"] .btn, .sales-actions[data-supplier-order-actions="${r}"] button`).forEach(l=>{l instanceof HTMLButtonElement&&(l.disabled=o)})};s(!0);try{const l=await(await fetch(`/supplier-orders/${r}/state`,{method:"PATCH",headers:{"X-CSRF-TOKEN":b(),Accept:"application/json","Content-Type":"application/json"},body:JSON.stringify({state:a})})).json();l.success?(await T({icon:"success",title:"Listo",text:l.message||n,timer:3600}),a==="confirmed"||a==="delivered"?window.location.reload():(B(String(r),a),f===String(r)&&O(a),s(!1))):(s(!1),await g(l.message||"No se pudo actualizar.","No se pudo completar"))}catch{s(!1),await g("Revisa tu red e inténtalo de nuevo.","Error de conexión")}}function X(r){const a=L(r);a!==null&&a!=="draft"&&a!=="pending"||q(r,"confirmed",{title:"¿Confirmar este pedido?",html:"<p>El pedido pasará a estado <strong>confirmado</strong> con el proveedor. Luego podrás registrar la <strong>recepción de mercancía</strong> al recibirla.</p>",icon:"question",confirm:"Sí, confirmar"},"Pedido confirmado correctamente.")}function U(r){const a=L(r);a!==null&&a!=="confirmed"||q(r,"delivered",{title:"¿Marcar como entregado?",html:"<p>Se registrará la <strong>recepción de la mercancía</strong> y se actualizará el inventario según las líneas del pedido.</p>",icon:"question",confirm:"Sí, marcar entregado"},"Pedido marcado como entregado.")}async function W(r){const a=await I(),t=await k({...N(),title:"¿Cancelar pedido?",html:`
            <p style="margin:0 0 12px; color:#4b5563;">El pedido se marcará como cancelado.</p>
            <textarea id="swal-cancel-reason"
                placeholder="Motivo de la cancelación…"
                style="width:100%; min-height:80px; resize:vertical; padding:8px 10px;
                       border:1px solid #d1d5db; border-radius:8px; font-size:0.9rem;
                       font-family:inherit; outline:none; box-sizing:border-box;"
            ></textarea>
            <div id="swal-cancel-hint"
                 style="font-size:0.76rem; color:#9ca3af; margin-top:5px; text-align:left; transition:color .15s;">
                Escribe al menos 4 caracteres para continuar.
            </div>`,icon:"warning",showCancelButton:!0,confirmButtonText:"Sí, cancelar",cancelButtonText:"Cancelar",customClass:{...A,confirmButton:"cf4-swal-btn cf4-swal-btn-danger"},didOpen:()=>{const s=a.getConfirmButton(),o=document.getElementById("swal-cancel-reason"),l=document.getElementById("swal-cancel-hint");s.disabled=!0,s.style.opacity="0.45",s.style.cursor="not-allowed",o.addEventListener("input",()=>{const d=o.value.trim().length>=4;s.disabled=!d,s.style.opacity=d?"1":"0.45",s.style.cursor=d?"":"not-allowed",l.style.color=d?"#235347":"#9ca3af",l.textContent=d?"✓ Motivo válido.":"Escribe al menos 4 caracteres para continuar."})},preConfirm:()=>{const s=document.getElementById("swal-cancel-reason").value.trim();return s.length<4?(a.showValidationMessage("El motivo debe tener al menos 4 caracteres."),!1):s}});if(!t.isConfirmed)return;const n=t.value,e=s=>{document.querySelectorAll(`tr[data-order-id="${r}"] .action-btn, #view-order-body [data-role="modal-actions"] .btn`).forEach(o=>{o instanceof HTMLButtonElement&&(o.disabled=s)})};e(!0);try{const o=await(await fetch(`/supplier-orders/${r}/state`,{method:"PATCH",headers:{"X-CSRF-TOKEN":b(),Accept:"application/json","Content-Type":"application/json"},body:JSON.stringify({state:"cancelled",reason:n})})).json();o.success?(await T({icon:"success",title:"Pedido cancelado",text:o.message||"El pedido fue cancelado correctamente.",timer:3e3}),B(String(r),"cancelled"),f===String(r)&&O("cancelled"),e(!1)):(e(!1),await g(o.message||"No se pudo cancelar.","Error"))}catch{e(!1),await g("Error de conexión.","Error")}}Object.assign(window,{closeViewOrderModal:j,closeViewSupplierModal:D,viewOrder:V,viewSupplier:K,confirmOrder:X,deliverOrder:U,cancelOrder:W});
