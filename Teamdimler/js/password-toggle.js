document.addEventListener("DOMContentLoaded", function() {
	document.querySelectorAll(".toggle-password").forEach(function(button) {
		button.addEventListener("click", function() {
			const container = button.closest(".campo-contrasena");
			const field = container && container.querySelector("input");
			if (!field) return;

			const visible = field.type === "password";
			field.type = visible ? "text" : "password";
			button.setAttribute("aria-pressed", String(visible));
			button.setAttribute("aria-label", visible ? "Ocultar contraseña" : "Mostrar contraseña");
		});
	});
});
