document.addEventListener("DOMContentLoaded", function () {

  // ✅ Ambil order dari localStorage
  const savedOrder = localStorage.getItem("lastCheckout");

  if (!savedOrder) {
    document.getElementById("productList").innerHTML =
      "<p>Tidak ada produk.</p>";
    return;
  }

  const order = JSON.parse(savedOrder);

  // ✅ Tampilkan Produk
  const productHTML = `
    <div class="product">
      <img src="${order.image}">
      <div>
        <b>${order.product_name}</b><br>
        Qty: ${order.qty}<br>
        Harga: Rp ${new Intl.NumberFormat("id-ID").format(order.price)}
      </div>
    </div>
  `;

  document.getElementById("productList").innerHTML = productHTML;

  // ✅ Hitung total
  const total = order.qty * order.price;

  document.getElementById("totalHarga").innerText =
    "Rp " + new Intl.NumberFormat("id-ID").format(total);


  // ✅ GOOGLE AUTOCOMPLETE
  const input = document.getElementById("alamatSearch");
  const autocomplete = new google.maps.places.Autocomplete(input, {
    fields: ["formatted_address"],
    types: ["geocode"]
  });

  autocomplete.addListener("place_changed", function () {
    const place = autocomplete.getPlace();
    document.getElementById("alamatLengkap").value = place.formatted_address;
  });


  // ✅ Tombol Buat Pesanan
  document.getElementById("btnPesan").addEventListener("click", function () {

    const nama = document.getElementById("namaPenerima").value;
    const hp = document.getElementById("noHp").value;
    const alamat = document.getElementById("alamatLengkap").value;
    const pembayaran = document.querySelector("input[name='pay']:checked");

    if (!nama || !hp || !alamat || !pembayaran) {
      alert("Lengkapi semua data!");
      return;
    }

    alert(
      "✅ PESANAN BERHASIL!\n\n" +
      "Nama: " + nama + "\n" +
      "Pembayaran: " + pembayaran.value + "\n" +
      "Alamat: " + alamat + "\n" +
      "Total: " + document.getElementById("totalHarga").innerText
    );
  });
});
