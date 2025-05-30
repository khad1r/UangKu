<style>
  .body {
    --body-min-height: 80vh;
    --body-mobile-margin-top: 20vh;
  }

  #chartContainer .highcharts-button,
  #chartContainer select,
  #chartContainer .highcharts-credits {
    display: none !important;
  }
</style>
<div class="wrapper">
  <div class="header">
    <?php $Controller->view('components/navbar', $data); ?>
    <div class="summary text-center d-grid align-content-center">
      <div class="transaction"><?= $data['store_name'] ?></div>
      <div class="store-name" style="font-size: .8em;"><?= $data['nmid'] ?></div>
    </div>
    <div style="height: 5vh;"></div>
  </div>

  <div class="body px-3">
    <div class="indicator-body">
      <div class="spinner-border" role="status">
      </div>
    </div>
    <div id="chartContainer"></div>
    <div id="total-transaction" class="mt-3 text-center" style='display: none;'>
      <div class="">Total Transaksi Melalui Qris</div>
      <span class="fs-4 fw-bold text-primary">Rp.&nbsp0,s</span>
    </div>
  </div>

</div>
<!-- <script src="<?= BASEURL ?>/assets/js/notification-helper.js"></script> -->
<script src="https://code.highcharts.com/stock/highstock.js" crossorigin="anonymous"></script>

<!-- <script>
  const totalDom = document.querySelector('#total-transaction')
  const loading = document.querySelector(".indicator-body");
  document.addEventListener('DOMContentLoaded', async e => {
    try {
      // Load the dataset
      const response = await fetch('Graph/data/year', {
        headers: {
          'Accept': 'application/json', // Specify the type you expect
        },
      });
      if (!response.ok) throw response; // Throws the response if not OK
      const data = await response.json();
      // Create the chart using Highcharts
      Highcharts.stockChart('chartContainer', {
        accessibility: {
          enabled: false
        },
        navigator: {
          height: 120
        },
        chart: {
          backgroundColor: null, // Set background color to transparent
          height: Math.max(Math.floor(window.innerHeight / window.innerWidth * 70), 70) + '%',
          events: {
            render(event) {
              const sum = data
                .filter(point => point[0] >= this.xAxis[0].min && point[0] <= this.xAxis[0].max) // Filter data based on the selected range
                .reduce((acc, point) => acc + point[1], 0); // Sum the values (assuming point[1] is the value)
              totalDom.style.display = 'block'
              loading.style.display = "none";
              totalDom.querySelector('span').innerHTML = `Rp.&nbsp${formattedNumber.format(sum)},-`
            }
          }
        },
        tooltip: {
          formatter() {
            return /* HTML */ `
            <span class="text-danger">${formatDate(this.x,{weekday: "long",day: "numeric",month: "short",year: "numeric"})}</span><br/>
            <b class="text-primary">Rp. ${formattedNumber.format(this.y)},-</b>`
          }
        },
        rangeSelector: {
          selected: 0,
          inputPosition: {
            align: 'center',
            x: 10
          },
          inputStyle: {
            color: 'var(--primary-color)',
            fontSize: '1.12rem',
            fontFamily: 'var(--font-stack)',
            fontWeight: '600'
          },
        },
        xAxis: {
          labels: {
            formatter: function() {
              return formatDate(this.value, {
                day: "numeric",
                month: "short",
              })
            }
          },
        },
        title: {
          text: 'Grafik Transaksi',
          style: {
            color: 'var(--primary-color)',
            fontSize: '1.5rem',
            fontFamily: 'var(--font-head)',
            fontWeight: '700'
          },
        },
        scrollbar: {
          enabled: false
        },
        series: [{
          name: 'Transaksi',
          data: data,
          lineWidth: 5,
          color: 'red',

        }],
      });
    } catch (error) {
      loading.style.display = "none";
      let errorMessage = ` Terjadi Kesalahan:<br><small>${error.statusText ?? error}.</small>`;
      if (error.status === 429) {
        errorMessage = 'Terlalu banyak permintaan.<br><small>Silakan coba lagi nanti.</small>';
      } else if (error.status === 401) {
        errorMessage = 'Unauthorized.<br><small>Silahkan login kembali.</small>';
        setTimeout(() => {
          sessionStorage.clear();
          removeCache()
          window.location.href = "<?= BASEURL ?>/Auth/Logout"
        }, 1500);
      } else if (error.status === 0) {
        errorMessage = 'Tidak dapat terhubung ke server.<br><small>Pastikan koneksi internet Anda stabil.</small>';
      } else if (error.status >= 400 && error.status < 500) {
        errorMessage = 'Terjadi kesalahan pada permintaan Anda. #202.';
      } else if (error.status >= 500) {
        errorMessage = 'Terjadi kesalahan pada server.<br><small>Silakan coba lagi nanti.</small>';
      }
      showAlert('danger', errorMessage)
      loadingPage.style.display = "none";
    }
  })
</script> -->