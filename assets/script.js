// Room rates
const roomRates = {
  "Standard Room": {
    "2 hrs": 200,
    "3 hrs": 300,
    "10 hrs": 600,
    "12 hrs": 800,
    "24 hrs": 1200
  },
  "Deluxe Room": {
    "2 hrs": 400,
    "3 hrs": 600,
    "10 hrs": 1200,
    "12 hrs": 1500,
    "24 hrs": 2200
  },
  "Suite Room": {
    "2 hrs": 700,
    "3 hrs": 1000,
    "10 hrs": 2000,
    "12 hrs": 2500,
    "24 hrs": 3500
  }
};

function selectRate(element, price) {
  document.querySelectorAll('.rate-card').forEach(card => {
    card.classList.remove('active');
  });

  element.classList.add('active');

  document.getElementById("totalPrice").innerText = "₱" + price;
}

function openBooking(roomName) {
  document.getElementById("roomName").innerText = roomName;

  const rateOptions = document.getElementById("rateOptions");
  rateOptions.innerHTML = "";

  Object.entries(roomRates[roomName]).forEach(([time, price]) => {
    const col = document.createElement("div");
    col.classList.add("col-6", "col-md-4");

    col.innerHTML = `
      <div class="card rate-card h-100 text-center p-3" onclick="selectRate(this, ${price})">
        <h5 class="fw-bold">${time}</h5>
        <p class="mb-0 text-muted">₱${price}</p>
      </div>
    `;

    rateOptions.appendChild(col);
  });

  document.getElementById("totalPrice").innerText = "₱0";
}

function selectRate(element, price) {
  document.querySelectorAll('.rate-card').forEach(card => {
    card.classList.remove('active');
  });

  element.classList.add('active');

  document.getElementById("totalPrice").innerText = "₱" + price;
}

document.querySelectorAll('.modal').forEach(modal => {
  modal.addEventListener('hidden.bs.modal', function () {
    const carousels = modal.querySelectorAll('.carousel');
    carousels.forEach(carousel => {
      const bsCarousel = bootstrap.Carousel.getInstance(carousel);
      if (bsCarousel) {
        bsCarousel.to(0);
      }
    });
  });
});

document.addEventListener("DOMContentLoaded", () => {
  const bookingDate = document.getElementById("bookingDate");
  const today = new Date().toISOString().split("T")[0];
  bookingDate.setAttribute("min", today);

  let isOpen = false;

  bookingDate.addEventListener("click", (e) => {
    if (bookingDate.showPicker) {
      if (!isOpen) {
        bookingDate.showPicker();
        isOpen = true;
      } else {
        e.target.blur();
        isOpen = false;
      }
    }
  });

  bookingDate.addEventListener("change", () => {
    isOpen = false;
  });
  bookingDate.addEventListener("blur", () => {
    isOpen = false;
  });
});
