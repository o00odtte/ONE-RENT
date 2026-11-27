// JAVASCRIPT UTAMA: mengatur interaksi antarmuka seperti tanggal, sidebar, modal, pencarian, dan validasi form.


document.addEventListener("DOMContentLoaded", function () {

    // TANGGAL
    const dateElement = document.getElementById("currentDate");

    if (dateElement) {
        dateElement.textContent = new Date().toLocaleDateString("id-ID", {
            day: "numeric",
            month: "long",
            year: "numeric"
        });
    }


    // SIDEBAR
    const sidebar = document.getElementById("sidebar");

    window.toggleSidebar = function () {
        if (sidebar) sidebar.classList.toggle("show");
    };

    document.querySelectorAll(".menu-toggle").forEach(button => {
        // Hindari klik dua kali jika tombol sudah memakai onclick
        if (!button.hasAttribute("onclick")) {
            button.addEventListener("click", window.toggleSidebar);
        }
    });


    // NOTIFIKASI
    window.closeAlert = function (alert) {
        if (!alert || !alert.parentElement) return;

        alert.style.transition = "opacity .3s ease, transform .3s ease";
        alert.style.opacity = "0";
        alert.style.transform = "translateY(-8px)";

        setTimeout(() => alert.remove(), 300);
    };

    document.querySelectorAll(
        ".alert-message, .customer-alert, .module-alert"
    ).forEach(alert => {
        setTimeout(() => window.closeAlert(alert), 3000);
    });


    // FORM TAMBAH MOBIL
    const formPanel = document.getElementById("carFormPanel");
    const openForm = document.getElementById("openForm");

    if (openForm && formPanel) {
        openForm.addEventListener("click", function () {
            formPanel.classList.add("show");
            formPanel.scrollIntoView({ behavior: "smooth" });
        });
    }

    function hideForm() {
        if (formPanel) formPanel.classList.remove("show");
    }

    document.getElementById("closeForm")?.addEventListener("click", hideForm);
    document.getElementById("cancelForm")?.addEventListener("click", hideForm);


    // PENCARIAN DAN FILTER MOBIL
    const searchCar = document.getElementById("searchCar");
    const filterStatus = document.getElementById("filterStatus");
    const tableBody = document.getElementById("carTableBody");
    const tableNote = document.getElementById("tableNote");

    function filterCars() {
        if (!searchCar || !filterStatus || !tableBody || !tableNote) return;

        const keyword = searchCar.value.toLowerCase();
        const status = filterStatus.value.toLowerCase();
        let jumlah = 0;

        tableBody.querySelectorAll("tr").forEach(row => {
            if (!row.dataset.status) return;

            const cocokKeyword = row.textContent.toLowerCase().includes(keyword);
            const cocokStatus = status === "" ||
                row.dataset.status.toLowerCase() === status;

            row.style.display = cocokKeyword && cocokStatus ? "" : "none";

            if (cocokKeyword && cocokStatus) jumlah++;
        });

        tableNote.textContent = "Menampilkan " + jumlah + " kendaraan.";
    }

    searchCar?.addEventListener("input", filterCars);
    filterStatus?.addEventListener("change", filterCars);


    // POPUP DETAIL MOBIL
    const detailModal = document.getElementById("detailModal");

    document.querySelectorAll(".detail-btn").forEach(button => {
        button.addEventListener("click", function () {
            document.getElementById("detailId").textContent = this.dataset.id;
            document.getElementById("detailMerk").textContent = this.dataset.merk;
            document.getElementById("detailPlat").textContent = this.dataset.plat;
            document.getElementById("detailSeat").textContent = this.dataset.seat;

            document.getElementById("detailTarif").textContent =
                "Rp" + Number(this.dataset.tarif).toLocaleString("id-ID");

            document.getElementById("detailStatus").textContent = this.dataset.status;

            detailModal?.classList.add("show");
            document.body.style.overflow = "hidden";
        });
    });

    function hideDetailModal() {
        detailModal?.classList.remove("show");
        document.body.style.overflow = "";
    }

    window.hideDetailModal = hideDetailModal;

    document.getElementById("closeDetail")?.addEventListener("click", hideDetailModal);
    document.getElementById("closeDetailBottom")?.addEventListener("click", hideDetailModal);


    // POPUP EDIT MOBIL
    const editFormPanel = document.getElementById("editFormPanel");

    document.querySelectorAll(".edit-btn").forEach(button => {
        button.addEventListener("click", function () {
            document.getElementById("editId").value = this.dataset.id;
            document.getElementById("editMerk").value = this.dataset.merk;
            document.getElementById("editPlat").value = this.dataset.plat;
            document.getElementById("editSeat").value = this.dataset.seat;
            document.getElementById("editTarif").value = this.dataset.tarif;
            document.getElementById("editStatus").value = this.dataset.status;

            editFormPanel?.classList.add("show");
            document.body.style.overflow = "hidden";
        });
    });

    function hideEditForm() {
        editFormPanel?.classList.remove("show");
        document.body.style.overflow = "";
    }

    window.hideEditForm = hideEditForm;

    document.getElementById("closeEditForm")?.addEventListener("click", hideEditForm);
    document.getElementById("cancelEditForm")?.addEventListener("click", hideEditForm);


    // POPUP HAPUS MOBIL
    const deleteModal = document.getElementById("deleteModal");

    document.querySelectorAll(".delete-btn").forEach(button => {
        button.addEventListener("click", function () {
            document.getElementById("deleteCarId").value = this.dataset.id;
            document.getElementById("deleteCarName").textContent = this.dataset.merk;
            document.getElementById("deleteCarPlate").textContent = this.dataset.plat;

            deleteModal?.classList.add("show");
            document.body.style.overflow = "hidden";
        });
    });

    function hideDeleteModal() {
        deleteModal?.classList.remove("show");
        document.body.style.overflow = "";
    }

    window.hideDeleteModal = hideDeleteModal;

    document.getElementById("cancelDelete")?.addEventListener("click", hideDeleteModal);


    // MENUTUP POPUP JIKA AREA LUAR DIKLIK
    [detailModal, editFormPanel, deleteModal].forEach(modal => {
        if (!modal) return;

        modal.addEventListener("click", function (event) {
            if (event.target === modal) {
                modal.classList.remove("show");
                document.body.style.overflow = "";
            }
        });
    });


    // IKON
    if (window.lucide) {
        window.lucide.createIcons();
    }


// =====================================
// FUNGSI BANTU MODAL PELANGGAN
// =====================================
function tampilkanModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;

    modal.classList.add("show");
    document.body.style.overflow = "hidden";
}

function tutupModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;

    modal.classList.remove("show");
    document.body.style.overflow = "";
}

// =====================================
// MODAL TAMBAH PELANGGAN
// =====================================
window.openAddModal = function () {
    const form = document.getElementById("customerForm");
    if (!form) return;

    form.reset();
    form.action = "pelanggan.php";

    document.getElementById("customerFormTitle").textContent = "Tambah Pelanggan";
    document.getElementById("formId").value = "";
    document.getElementById("customerSubmit").name = "tambah_pelanggan";
    document.getElementById("customerSubmit").textContent = "Simpan";

    resetValidasiAlamat();
    tampilkanModal("customerFormModal");
};

// =====================================
// MODAL EDIT PELANGGAN
// =====================================
window.showEdit = function (button) {
    const data = button.dataset;
    const form = document.getElementById("customerForm");

    if (!form) return;

    form.reset();
    form.action = "pelanggan.php";

    document.getElementById("customerFormTitle").textContent = "Edit Pelanggan";
    document.getElementById("formId").value = data.id;
    document.getElementById("formNama").value = data.nama;
    document.getElementById("formHP").value = data.hp;
    document.getElementById("formKTP").value = data.ktp;
    document.getElementById("formSIM").value = data.sim;
    document.getElementById("formTanggal").value = data.tanggal;
    document.getElementById("formAlamat").value = data.alamat;

    document.getElementById("customerSubmit").name = "edit_pelanggan";
    document.getElementById("customerSubmit").textContent = "Simpan Perubahan";

    resetValidasiAlamat();
    tampilkanModal("customerFormModal");
};

// =====================================
// MODAL DETAIL PELANGGAN
// =====================================
window.showDetail = function (button) {
    const data = button.dataset;

    document.getElementById("detailId").textContent = data.id;
    document.getElementById("detailNama").textContent = data.nama;
    document.getElementById("detailHP").textContent = data.hp;
    document.getElementById("detailKTP").textContent = data.ktp;
    document.getElementById("detailSIM").textContent = data.sim;
    document.getElementById("detailTanggal").textContent = data.tanggal || "-";
    document.getElementById("detailAlamat").textContent = data.alamat || "-";

    tampilkanModal("customerDetailModal");
};

// =====================================
// MODAL HAPUS PELANGGAN
// =====================================
window.showDelete = function (button) {
    const data = button.dataset;

    document.getElementById("hapusId").value = data.id;
    document.getElementById("deleteNama").textContent = data.nama;

    tampilkanModal("customerDeleteModal");
};

// =====================================
// TUTUP MODAL
// =====================================
window.closeCustomerForm = function () {
    tutupModal("customerFormModal");
};

window.closeDetailModal = function () {
    tutupModal("customerDetailModal");
};

window.closeDeleteModal = function () {
    tutupModal("customerDeleteModal");
};

// =====================================
// KLIK DI LUAR MODAL
// =====================================
document.querySelectorAll(".customer-modal").forEach(function (modal) {
    modal.addEventListener("click", function (event) {
        if (event.target === modal) {
            modal.classList.remove("show");
            document.body.style.overflow = "";
        }
    });
});

// =====================================
// PENCARIAN PELANGGAN
// =====================================
const searchPelanggan = document.getElementById("searchPelanggan");

if (searchPelanggan) {
    searchPelanggan.addEventListener("input", function () {
        const keyword = this.value.toLowerCase();
        const rows = document.querySelectorAll("#tablePelanggan tbody tr");
        let ditemukan = 0;

        rows.forEach(function (row) {
            const cocok = row.textContent.toLowerCase().includes(keyword);
            row.style.display = cocok ? "" : "none";

            if (cocok) ditemukan++;
        });

        const hasilKosong = document.getElementById("noSearchResult");
        if (hasilKosong) {
            hasilKosong.style.display = ditemukan === 0 ? "block" : "none";
        }
    });
}

// =====================================
// VALIDASI ALAMAT
// =====================================
const inputAlamat = document.getElementById("formAlamat");
const alamatError = document.getElementById("alamatError");

function resetValidasiAlamat() {
    if (inputAlamat) inputAlamat.setCustomValidity("");
    if (alamatError) alamatError.style.display = "none";
}

function validasiAlamat() {
    if (!inputAlamat || !alamatError) return true;

    const alamat = inputAlamat.value.trim();
    const valid = /[a-zA-Z]/.test(alamat);

    alamatError.style.display = valid ? "none" : "block";

    inputAlamat.setCustomValidity(
        valid ? "" : "Alamat harus diisi dengan alamat yang valid."
    );

    return valid;
}

if (inputAlamat) {
    inputAlamat.addEventListener("input", validasiAlamat);
}

const customerForm = document.getElementById("customerForm");

if (customerForm) {
    customerForm.addEventListener("submit", function (event) {
        if (!validasiAlamat()) {
            event.preventDefault();
            inputAlamat.reportValidity();
        }
    });
}

});
