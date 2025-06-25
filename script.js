let users = JSON.parse(localStorage.getItem("users") || "[]");
let menuItems = JSON.parse(localStorage.getItem("menuItems") || "[]");
let orders = JSON.parse(localStorage.getItem("orders") || "[]");

// User Management
function addUser(event) {
  event.preventDefault();
  const username = document.getElementById("newUser").value;
  const role = document.getElementById("userRole").value;
  users.push({ username, role });
  localStorage.setItem("users", JSON.stringify(users));
  loadUsers();
  event.target.reset();
}

function loadUsers() {
  const userList = document.getElementById("userList");
  if (!userList) return;
  userList.innerHTML = "";
  users.forEach((user, index) => {
    const li = document.createElement("li");
    li.textContent = `${user.username} (${user.role}) `;
    const delBtn = document.createElement("button");
    delBtn.textContent = "Delete";
    delBtn.onclick = () => {
      users.splice(index, 1);
      localStorage.setItem("users", JSON.stringify(users));
      loadUsers();
    };
    li.appendChild(delBtn);
    userList.appendChild(li);
  });
}
// Replace 'loggedInUsername' with actual logged-in username variable
showUserInfo(loggedInUsername);

// Menu Management
function addMenuItem(event) {
  event.preventDefault();
  const name = document.getElementById("itemName").value;
  const price = parseFloat(document.getElementById("itemPrice").value);
  const stock = parseInt(document.getElementById("itemStock").value);
  const existing = menuItems.find(i => i.name === name);
  if (existing) {
    existing.price = price;
    existing.stock = stock;
  } else {
    menuItems.push({ id: menuItems.length + 1, name, price, stock });
  }
  localStorage.setItem("menuItems", JSON.stringify(menuItems));
  loadMenuItems();
  event.target.reset();
}

function loadMenuItems() {
  const menuList = document.getElementById("menuList");
  if (!menuList) return;
  menuList.innerHTML = "";
  menuItems.forEach((item, index) => {
    const li = document.createElement("li");
    li.textContent = `${item.name} - ₹${item.price} (Stock: ${item.stock}) `;
    const delBtn = document.createElement("button");
    delBtn.textContent = "Delete";
    delBtn.onclick = () => {
      menuItems.splice(index, 1);
      localStorage.setItem("menuItems", JSON.stringify(menuItems));
      loadMenuItems();
    };
    li.appendChild(delBtn);
    menuList.appendChild(li);
  });
}

// Orders
function loadOrders() {
  const orderList = document.getElementById("orderList");
  if (!orderList) return;
  orderList.innerHTML = "";
  orders.forEach((order, i) => {
    const li = document.createElement("li");
    li.textContent = `Order ${i + 1}: ${order.items.join(", ")} - ₹${order.total} - Status: ${order.status}`;
    orderList.appendChild(li);
  });
}