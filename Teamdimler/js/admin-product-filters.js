document.addEventListener("DOMContentLoaded", function() {
	const search = document.getElementById("admin-buscar-producto");
	const category = document.getElementById("admin-filtrar-categoria");
	const status = document.getElementById("admin-filtrar-estado");
	const resultCount = document.getElementById("admin-productos-resultados");
	const emptyMessage = document.getElementById("admin-productos-sin-resultados");
	const rows = Array.from(document.querySelectorAll("#productos-admin-titulo tbody tr[data-producto-nombre]"));

	if (!search || !category || !status || !resultCount || !emptyMessage || rows.length === 0) return;

	const normalize = function(value) {
		return value.toLocaleLowerCase("es").normalize("NFD").replace(/[\u0300-\u036f]/g, "");
	};

	const filterProducts = function() {
		const query = normalize(search.value.trim());
		const selectedCategory = category.value;
		const selectedStatus = status.value;
		let visibleCount = 0;

		rows.forEach(function(row) {
			const matches = normalize(row.dataset.productoNombre || "").includes(query)
				&& (!selectedCategory || row.dataset.productoCategoria === selectedCategory)
				&& (!selectedStatus || row.dataset.productoActivo === selectedStatus);
			row.hidden = !matches;
			if (matches) visibleCount += 1;
		});

		resultCount.textContent = visibleCount + (visibleCount === 1 ? " producto" : " productos");
		emptyMessage.hidden = visibleCount !== 0;
	};

	search.addEventListener("input", filterProducts);
	category.addEventListener("change", filterProducts);
	status.addEventListener("change", filterProducts);
});
