<div class="wrapper">
  <div class="header">
    <?php $this->view('components/navbar', $data); ?>
    <div class="summary text-center d-grid align-content-center">
      <div class="store-name"><?= $data['store_name'] ?></div>
      <div class="transaction"></div>
    </div>
    <div id="date-select-container">
      <div class="d-flex flex-row" id="date-select">
        <label id="date" class="d-flex flex-column date-select justify-content-center text-center"></label>
        <input id="date" style="width: 0%; border: none; opacity:0" type="date" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}">
        <div class="d-flex flex-column date date-select justify-content-center text-center">
        </div>
        <div class="d-flex flex-column date date-select justify-content-center text-center">
        </div>
        <div class="d-flex flex-column date date-select justify-content-center text-center">
        </div>
        <div class="d-flex flex-column date date-select justify-content-center text-center">
        </div>
        <div class="d-flex flex-column date date-select justify-content-center text-center">
        </div>
        <div class="d-flex flex-column date date-select justify-content-center text-center">
        </div>
        <div class="d-flex flex-column date date-select justify-content-center text-center">
        </div>

      </div>
    </div>
  </div>

  <div class="body px-3">
    <div class="indicator-body">
      <div class="spinner-border" role="status">
      </div>
    </div>
    <table class="table table-responsive myTable" id="FormatTable">
    </table>

    <div style="height: 10vh;">

    </div>
  </div>

</div>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs5/jq-3.6.0/dt-1.13.1/datatables.min.css" crossorigin="anonymous" />
<script type="text/javascript" src="https://cdn.datatables.net/v/bs5/jq-3.6.0/dt-1.13.1/datatables.min.js" crossorigin="anonymous"></script>
<!-- <script src="<?= BASEURL ?>/assets/js/notification-helper.js"></script> -->
<script>
  getServiceWorker()
  sessionStorage.setItem('notification_id', "<?= base64_encode($_SESSION['user']['merchant_transaction_reff']) ?>")
  const labelDate = document.querySelector("label#date");
  const datePicker = document.querySelector("input#date");
  datePicker.max = datePicker.value = new Date().toISOString().split('T')[0];
  const totalTransaction = document.querySelector("div.transaction");
  const loading = document.querySelector(".indicator-body");
  const dayMap = document.querySelectorAll('.date');
  const dateSelectContainer = document.querySelector("#date-select-container");
  const itemToScroll = document.querySelector("#date-select");

  dateSelectContainer.addEventListener("wheel", function(e) {
    if (Math.abs(e.deltaY) > 0) {
      e.preventDefault();
      itemToScroll.scrollLeft += e.deltaY;
    }
  });
  const maxToast = 2
  let today;
  let isToday = false;
  let isRateLimit = false;
  let SelectedIndex
  let Ftable
  let init = true

  labelDate.addEventListener("click", () => {
    datePicker.showPicker();
    datePicker.focus()
    datePicker.click()
  })
  datePicker.addEventListener("change", async () => {
    await updateDateLabel();
    focusToSelected();
  });

  dayMap.forEach((element, index) => {
    element.addEventListener('click', () => {
      element.classList.remove('active');
      if (SelectedIndex == index) {
        setTimeout(() => {
          highlightTheDay(index); // Pass the index to the highlightTheDay function
        }, 200);
      } else {
        highlightTheDay(index); // Pass the index to the highlightTheDay function
      }
      SelectedIndex = index;
      focusToSelected();
    });
  });

  function updateDateLabel() {
    const selectedDate = new Date(datePicker.value);

    // Get the year and month from the selected date
    const year = selectedDate.getFullYear();
    const month = selectedDate.getMonth(); // 0-based index (January = 0)
    const day = selectedDate.getDate();
    // Calculate the start of the week
    const startOfWeek = new Date(selectedDate);
    startOfWeek.setDate(selectedDate.getDate() - selectedDate.getDay()); // Adjust to start of week

    for (let i = 0; i < dayMap.length; i++) {
      const currentDate = new Date(startOfWeek);
      currentDate.setDate(startOfWeek.getDate() + i);

      dayMap[i]['date'] = toDateNumeric(currentDate);
      dayMap[i].classList.remove("today");

      if (toDateNumeric(currentDate) === toDateNumeric(new Date())) dayMap[i].classList.add("today");
      if (toDateNumeric(currentDate) === toDateNumeric(selectedDate)) {
        highlightTheDay(i); // Pass the index to the highlightTheDay function
        SelectedIndex = i
      }

      dayMap[i].innerHTML = /* HTML */ `
        <small class="top">${toWeekday(currentDate)}</small>
        <div class="mid">${toDate(currentDate)}</div>
        <small class="bot">${toMonthShort(currentDate)}</small>
      `;
    }

    labelDate.innerHTML = /* HTML */ `
      <small class="top">${toYear(selectedDate)}</small>
      <div class="mid"><i class="fa-regular fa-calendar"></i></div>
      <small class="bot">${toMonthShort(selectedDate)}</small>
    `;
  }

  async function highlightTheDay(index) {
    await dayMap.forEach(element => element.classList.remove('active'));
    dayMap[index].classList.add("active");

    if (!init) Ftable.draw()

    isToday = (dayMap[index].date === today) && (Ftable.page.info().page === 0);
  }

  function focusToSelected() {
    // Get the container
    const container = document.querySelector('#date-select');

    // Calculate the position of the element relative to the container
    const element = dayMap[SelectedIndex];
    const containerRect = container.getBoundingClientRect();
    const elementRect = element.getBoundingClientRect();

    // Center the element within the container
    const offset = elementRect.left - containerRect.left;
    const containerScrollLeft = container.scrollLeft;
    const containerWidth = container.clientWidth;

    // Calculate the scroll position
    const scrollPosition = offset - (containerWidth / 2) + (elementRect.width / 2);

    // Smoothly scroll to the calculated position
    container.scrollTo({
      left: scrollPosition + containerScrollLeft,
      behavior: 'smooth'
    });
  }

  async function generateHeader(data) {
    totalTransaction.innerHTML = `Rp.&nbsp${formattedNumber.format(data.total)},- (${data.jumlah})`
  }
  document.addEventListener('DOMContentLoaded', async () => {
    await updateDateLabel();
    today = toDateNumeric(new Date())
    SelectedIndex = Object.keys(dayMap).find(key => dayMap[key].date === today);
    highlightTheDay(SelectedIndex);
    focusToSelected();
    $.fn.dataTable.ext.errMode = 'none';
    Ftable = $("#FormatTable").DataTable({
      "dom": "tp",
      "language": {
        "lengthMenu": "Menampilkan _MENU_ baris per halaman",
        "zeroRecords": "Tidak ada transaksi",
        "infoEmpty": "Tidak ada transaksi",
        "search": "Cari : ",
        "paginate": {
          "first": "⇚",
          "last": "⇛",
          "next": "⇒",
          "previous": "⇐"
        }
      },
      'processing': true,
      'serverSide': true,
      'bAutoWidth': false,
      'ajax': {
        'url': '<?= BASEURL . '/Main/queryTransaction' ?>',
        'type': 'POST',
        'data': (post_data) => {
          loading.style.display = "grid";
          // Append SelectedDate to the POST data
          post_data.selectedDate = dayMap[SelectedIndex].date;
          // post_data.mutasiMode = mutasiMode;
          return post_data;
        },
        "dataSrc": (json) => {
          generateHeader(json.total)
          return json.data; // Return the data part of the response
        },
        "error": async (xhr, error, code) => {
          let errorMessage = ` Terjadi Kesalahan: ${code}`;

          if (xhr.status === 429) {
            errorMessage = 'Terlalu banyak permintaan.<br><small>Silakan coba lagi nanti.</small>';
            isRateLimit = true;
            setTimeout(() => {
              isRateLimit = false
            }, 60000)
          } else if (xhr.status === 401) {
            errorMessage = 'Unauthorized.<br><small>Silahkan login kembali</small>';
            setTimeout(() => {
              sessionStorage.clear();
              removeCache()
              window.location.href = "<?= BASEURL ?>/Auth/Logout"
            }, 1500);
          } else if (xhr.status === 0) {
            errorMessage = 'Tidak dapat terhubung ke server.<br><small>Pastikan koneksi internet Anda stabil.</small>';
          } else if (xhr.status >= 400 && xhr.status < 500) {
            errorMessage = 'Terjadi kesalahan pada permintaan Anda. #102.';
          } else if (xhr.status >= 500) {
            errorMessage = 'Terjadi kesalahan pada server.<br><small>Silakan coba lagi nanti.</small>';
          }
          if (Ftable.rows().data().toArray().length === 0) {

            Ftable.settings()[0].oFeatures.bServerSide = false;
            const fallback = {
              "draw": 1,
              "recordsTotal": 1,
              "recordsFiltered": 0,
              "data": [],
              "total": {
                "total": "0.00",
                "jumlah": 0
              }
            };
            await Ftable.clear().rows.add(fallback).draw();
            // Re-enable server-side processing after the fallback data has been added
            Ftable.settings()[0].oFeatures.bServerSide = true;
          }
          showAlert('danger', errorMessage)
          loadingPage.style.display = "none";
          loading.style.display = "none";
        }
      },
      "order": [
        [0, "desc"]
      ],
      "columnDefs": [{
        "targets": [0], // Index of the hidden column
        "visible": false // Hide this column
      }],
      'columns': [{
          'data': 'id',
        },
        {
          width: '60%',
          'data': function(data, type, dataToSet) {
            return /* HTML */ `
          <strong class="kuitansi small font-italic">trans reff : ${data.kuitansi}</strong>
          <br>
          <strong class='keterangan text-shorted'>${data.buyer_vendor} - ${data.buyer_reff}</strong>`;
          }
        },
        {
          width: '40%',
          'data': function(data, type, dataToSet) {
            return /* HTML */ `
              <h6 class="nominal text-success fw-bold text-end">+Rp.&nbsp${formattedNumber.format(data.nominal)},-</h6>
              `
          }
        }

      ],
      "initComplete": function(settings, json) {
        // todayTrans = Ftable.rows().data().toArray()
        init = !init
      },
      "drawCallback": function(settings) {
        loading.style.display = "none";
        $("#FormatTable thead").remove();
      }
    });
    $("#FormatTable thead").remove();

    CHANNEL.addEventListener('message', (event) => {
      if (event.data.type === 'notify' && isToday && !init) Ftable.draw()
    })
  })
</script>