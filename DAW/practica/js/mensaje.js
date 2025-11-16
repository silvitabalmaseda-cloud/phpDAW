function $(id) { return document.getElementById(id); }

// Mostrar error debajo de un campo o grupo
function mostrarErrorCampo(campo, mensaje) {
    if (!campo) {
        return;
    }

    // si es un grupo de radios, mostrar en el contenedor padre <p>
    if (campo.length && campo[0].name === "tipo_mensaje") {
        campo = campo[0].closest("p.tipo-mensaje");
    }

    const contenedor = campo.closest("p") || campo;
    contenedor.classList.add('campo-error');

    // eliminar error anterior
    let eliminar = contenedor.nextElementSibling;
    if (eliminar && eliminar.classList.contains('error-campo')) {
        eliminar.remove();
    }

    const span = document.createElement('span');
    span.className = 'error-campo';
    span.textContent = mensaje;
    campo.insertAdjacentElement('afterend', span);
}

function validarMensaje(event) {
    event.preventDefault();
    let ok = true;

    // limpiar errores previos
    document.querySelectorAll('.error-campo').forEach(e => e.remove());
    document.querySelectorAll('.campo-error').forEach(e => e.classList.remove('campo-error'));
    

    // Tipo de mensaje: admitir tanto <select name="tipo_mensaje"> como radios
    const controlesTipo = document.getElementsByName("tipo_mensaje");
    let tipoOk = false;
    if (controlesTipo.length > 0) {
        const first = controlesTipo[0];
        if (first.tagName === 'SELECT') {
            tipoOk = first.value !== '' && first.value !== null;
        } else {
            tipoOk = Array.from(controlesTipo).some(r => r.checked);
        }
    }
    if (!tipoOk) {
        mostrarErrorCampo(controlesTipo, "Debes seleccionar un tipo de mensaje.");
        ok = false;
    }

    // Texto del mensaje (mínimo 10 caracteres no vacíos)
    const area = $("mensaje");
    const texto = area ? area.value.trim() : "";
    if (texto.length < 10) {
        mostrarErrorCampo(area, "Escribe al menos 10 caracteres en el mensaje.");
        ok = false;
    }

    if (ok) {
        $("formMensaje").submit();
    }
}

function load() {
    const formMensaje = $("formMensaje");
    if (formMensaje) {
        formMensaje.addEventListener("submit", validarMensaje);
    }

    // Radios para "tipo_mensaje"
    const contenedor = document.querySelector("p.tipo-mensaje");
    if (contenedor && !contenedor.querySelector('[name="tipo_mensaje"]')) {
        const opciones = [
            { valor: "mas informacion", texto: " Más información" },
            { valor: "solicitar cita", texto: " Solicitar cita" },
            { valor: "comunicar oferta", texto: " Comunicar oferta" }
        ];

        opciones.forEach(op => {
            const input = document.createElement("input");
            input.name = "tipo_mensaje";
            input.value = op.valor;
            input.setAttribute("type", "radio"); // asignado dinámicamente

            const label = document.createElement("label");
            label.appendChild(input);
            label.appendChild(document.createTextNode(op.texto));

            contenedor.appendChild(label);
            contenedor.appendChild(document.createTextNode(" "));
        });
    }

    // Limpiar errores al escribir/cambiar
    document.querySelectorAll('input, textarea').forEach(ctrl => {
        const limpiar = () => {
            ctrl.classList.remove('campo-error');
            const elim = ctrl.nextElementSibling;
            if (elim && elim.classList.contains('error-campo')) { elim.remove(); }
        };
        ctrl.addEventListener('input', limpiar);
        ctrl.addEventListener('change', limpiar);
    });
}

document.addEventListener("DOMContentLoaded", load);