document.addEventListener("DOMContentLoaded", function() {
	const PRODUCTS_KEY = "teamdimler-admin-demo-products";
	const ORDERS_KEY = "teamdimler-admin-demo-orders";
	const LOW_STOCK_THRESHOLD = 3;
	const currency = new Intl.NumberFormat("es-AR", { style: "currency", currency: "ARS" });

	const sampleProducts = [
		{ id: 1, name: "Combo de librería", category: "libreria", price: 2600, image: "img/generico libreria.webp", stock: 2, active: true },
		{ id: 2, name: "Kit de costura", category: "costura", price: 3200, image: "img/Generico.webp", stock: 8, active: true },
		{ id: 3, name: "Flores de crochet", category: "crochet", price: 2400, image: "img/Generico.webp", stock: 3, active: true }
	];
	const sampleOrders = [
		{ id: 1001, customer: "Cliente de ejemplo", date: new Date().toISOString(), status: "pendiente", total: 5800, items: ["Combo de librería × 1", "Flores de crochet × 1"] },
		{ id: 1002, customer: "Otra persona", date: new Date(Date.now() - 86400000).toISOString(), status: "preparando", total: 3200, items: ["Kit de costura × 1"] }
	];

	const load = function(key, initial) {
		try {
			const stored = localStorage.getItem(key);
			return stored ? JSON.parse(stored) : initial;
		} catch (error) {
			console.error("No se pudieron leer los datos de demostración.", error);
			return initial;
		}
	};
	const save = function(key, data) {
		try {
			localStorage.setItem(key, JSON.stringify(data));
		} catch (error) {
			console.error("No se pudieron guardar los datos de demostración.", error);
			window.alert("No se pudieron guardar los cambios en este navegador.");
		}
	};

	let products = load(PRODUCTS_KEY, sampleProducts);
	let orders = load(ORDERS_KEY, sampleOrders);
	if (Array.isArray(products)) {
		let migratedProducts = false;
		products = products.map(function(product) {
			if (Object.prototype.hasOwnProperty.call(product, "stock")) return product;
			const sample = sampleProducts.find(function(sampleProduct) {
				return sampleProduct.id === product.id;
			});
			migratedProducts = true;
			return Object.assign({}, product, { stock: sample ? sample.stock : null });
		});
		if (migratedProducts) save(PRODUCTS_KEY, products);
	} else {
		console.error("Los productos de demostración no tienen un formato válido.");
		products = sampleProducts.slice();
		save(PRODUCTS_KEY, products);
	}
	if (!localStorage.getItem(PRODUCTS_KEY)) save(PRODUCTS_KEY, products);
	if (!localStorage.getItem(ORDERS_KEY)) save(ORDERS_KEY, orders);

	const create = function(tag, text, className) {
		const element = document.createElement(tag);
		if (text !== undefined) element.textContent = text;
		if (className) element.className = className;
		return element;
	};

	const render = function() {
		const lowStockProducts = products.filter(function(product) {
			return Number.isInteger(product.stock) && product.stock <= LOW_STOCK_THRESHOLD;
		});
		document.getElementById("metric-productos").textContent = String(products.length);
		document.getElementById("admin-productos-cantidad").textContent = products.length + " productos en total";
		document.getElementById("metric-stock-bajo").textContent = String(lowStockProducts.length);
		document.getElementById("metric-pedidos").textContent = String(orders.filter(function(order) {
			return order.status === "pendiente";
		}).length);
		document.getElementById("metric-ventas").textContent = currency.format(orders.reduce(function(sum, order) {
			return sum + Number(order.total);
		}, 0));

		const lowStockAlert = document.getElementById("admin-demo-alerta-stock");
		const lowStockList = document.getElementById("admin-demo-lista-stock-bajo");
		lowStockAlert.hidden = lowStockProducts.length === 0;
		lowStockList.replaceChildren();
		lowStockProducts.forEach(function(product) {
			const item = document.createElement("li");
			const link = create("a", product.name);
			link.href = "#stock-demo-" + product.id;
			item.appendChild(link);
			item.appendChild(create("strong", product.stock + (product.stock === 1 ? " unidad" : " unidades")));
			lowStockList.appendChild(item);
		});

		const productTable = document.createElement("table");
		productTable.className = "tabla-carrito";
		productTable.innerHTML = "<thead><tr><th>Producto</th><th>Cantidad (demo)</th><th>Categoría</th><th>Precio</th><th>Estado</th><th>Acción</th></tr></thead>";
		const productBody = document.createElement("tbody");
		const search = document.getElementById("admin-demo-buscar");
		const category = document.getElementById("admin-demo-categoria");
		const status = document.getElementById("admin-demo-estado");
		const query = search.value.trim().toLocaleLowerCase("es").normalize("NFD").replace(/[\u0300-\u036f]/g, "");
		const filteredProducts = products.filter(function(product) {
			const name = product.name.toLocaleLowerCase("es").normalize("NFD").replace(/[\u0300-\u036f]/g, "");
			return name.includes(query)
				&& (!category.value || product.category === category.value)
				&& (!status.value || String(product.active) === status.value);
		});

		filteredProducts.forEach(function(product) {
			const row = document.createElement("tr");
			row.id = "producto-demo-" + product.id;
			row.appendChild(create("td", product.name));

			const stockCell = create("td");
			stockCell.id = "stock-demo-" + product.id;
			stockCell.appendChild(create("strong", Number.isInteger(product.stock) ? product.stock + " unidades" : "Sin cargar"));
			if (Number.isInteger(product.stock) && product.stock <= LOW_STOCK_THRESHOLD) {
				stockCell.appendChild(create("span", "Poco stock", "admin-stock-bajo"));
			}
			const stockForm = document.createElement("form");
			stockForm.className = "admin-stock-form";
			const stockInput = document.createElement("input");
			stockInput.type = "number";
			stockInput.min = "0";
			stockInput.step = "1";
			stockInput.placeholder = "Unidades";
			stockInput.value = Number.isInteger(product.stock) ? String(product.stock) : "";
			stockInput.setAttribute("aria-label", "Cantidad para " + product.name);
			stockInput.required = true;
			const stockButton = create("button", "Guardar", "boton-secundario");
			stockButton.type = "submit";
			stockForm.append(stockInput, stockButton);
			stockForm.addEventListener("submit", function(event) {
				event.preventDefault();
				product.stock = Number(stockInput.value);
				save(PRODUCTS_KEY, products);
				render();
			});
			stockCell.appendChild(stockForm);
			row.appendChild(stockCell);
			row.appendChild(create("td", product.category.charAt(0).toUpperCase() + product.category.slice(1)));
			row.appendChild(create("td", currency.format(Number(product.price))));
			row.appendChild(create("td", product.active ? "Publicado" : "Desactivado"));

			const actionCell = create("td");
			const toggle = create("button", product.active ? "Desactivar" : "Publicar", "boton-secundario");
			toggle.type = "button";
			toggle.addEventListener("click", function() {
				product.active = !product.active;
				save(PRODUCTS_KEY, products);
				render();
			});
			actionCell.appendChild(toggle);
			row.appendChild(actionCell);
			productBody.appendChild(row);
		});
		productTable.appendChild(productBody);
		const productsContainer = document.getElementById("admin-productos");
		productsContainer.replaceChildren();
		productsContainer.hidden = filteredProducts.length === 0;
		document.getElementById("admin-demo-sin-resultados").hidden = filteredProducts.length !== 0;
		document.getElementById("admin-demo-resultados").textContent = filteredProducts.length + (filteredProducts.length === 1 ? " producto" : " productos");
		if (filteredProducts.length) productsContainer.appendChild(productTable);

		const ordersContainer = document.getElementById("admin-pedidos");
		ordersContainer.replaceChildren();
		orders.forEach(function(order) {
			const card = create("article", undefined, "pedido-card");
			card.appendChild(create("h4", "Pedido #" + order.id + " — " + order.customer));
			card.appendChild(create("p", new Date(order.date).toLocaleString("es-AR")));
			const list = document.createElement("ul");
			order.items.forEach(function(item) {
				list.appendChild(create("li", item));
			});
			card.appendChild(list);
			card.appendChild(create("p", "Total: " + currency.format(Number(order.total))));
			const label = create("label", "Estado: ");
			const select = document.createElement("select");
			["pendiente", "preparando", "entregado", "cancelado"].forEach(function(status) {
				const option = create("option", status.charAt(0).toUpperCase() + status.slice(1));
				option.value = status;
				option.selected = order.status === status;
				select.appendChild(option);
			});
			select.addEventListener("change", function() {
				order.status = select.value;
				save(ORDERS_KEY, orders);
				render();
			});
			label.appendChild(select);
			card.appendChild(label);
			ordersContainer.appendChild(card);
		});
	};

	const form = document.getElementById("admin-producto-form");
	document.getElementById("admin-mostrar-formulario").addEventListener("click", function() {
		form.hidden = !form.hidden;
	});
	form.addEventListener("submit", function(event) {
		event.preventDefault();
		const data = new FormData(form);
		products.unshift({
			id: Date.now(),
			name: String(data.get("nombre")).trim(),
			category: String(data.get("categoria")),
			stock: data.get("stock") === "" ? null : Number(data.get("stock")),
			price: Number(data.get("precio")),
			image: String(data.get("imagen") || "img/Generico.webp").trim(),
			active: true
		});
		save(PRODUCTS_KEY, products);
		form.reset();
		form.hidden = true;
		render();
	});

	["admin-demo-buscar", "admin-demo-categoria", "admin-demo-estado"].forEach(function(id) {
		document.getElementById(id).addEventListener("input", render);
		document.getElementById(id).addEventListener("change", render);
	});

	render();
});
