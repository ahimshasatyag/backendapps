<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Cform extends CI_Controller
{

    public $global = array();
    public $id_menu = '10704';

    public function __construct()
    {
        parent::__construct();
        cek_session();

        $data = check_role($this->id_menu, 2);
        if (!$data) {
            redirect(base_url(), 'refresh');
        }

        $this->global['folder'] = $data[0]['nm_folder'];
        $this->global['title'] = $data[0]['nm_menu'];

        $this->load->model($this->global['folder'] . '/mmaster');
    }

    public function index()
    {
        $data = array(
            'folder' => $this->global['folder'],
            'title' => $this->global['title'],
        );

        $this->Logger->write('Membuka Menu ' . $this->global['title']);

        $this->load->view($this->global['folder'] . '/vformlist', $data);
    }

		public function data_table()
		{

			$folder = $this->global['folder'];

			$query = "select y.* from(
				select a.id_so, a.id_sales_contract, d.code_sales_contract, a.date_so , a.code_so , b.nm_customers
        from tb_so_hdr a
        left join m_customers b on (a.id_customers = b.id_customers )
        left join m_karyawan c on (a.id_karyawan = c.id_karyawan )
        inner join tb_sales_contract_hdr d on (a.id_sales_contract = d.id_sales_contract)
        where a.status_so in('SALE TO INVOICE','SALES ORDER')
				order by a.date_so DESC
	) as y";

			$requestData = $this->input->get();
			$totalData = $this->db->query($query)->num_rows();
			$totalFiltered = $totalData;

			if (!empty($requestData['search']['value'])) {
				$cari = [];

                $cari_tmp = $requestData['search']['value'];

                if($cari_tmp){
                    $cari_tmp = strtoupper($cari_tmp);
                }

				foreach (explode(',', $cari_tmp) as $word) {
									$cari[] = "UPPER(y.code_sales_contract) like '%$word%'";
									$cari[] = "UPPER(y.code_so) like '%$word%'";
									$cari[] = "UPPER(y.nm_customers) like '%$word%'";

				}

				$hasil_cari = implode(" OR ", $cari);

				$query .= " where ( $hasil_cari )";
			}


			$columns = array(
				0 => 'nm_customers',
				1 => 'code_sales_contract',
				2 => 'code_so',
				3 => 'date_so',
			);

			if (!empty($requestData['search']['value'])) {
					$totalFiltered = $this->db->query($query)->num_rows();
			}

			$query .= " ORDER BY " . $columns[$requestData['order'][0]['column']] . "   " . $requestData['order'][0]['dir'] . "  LIMIT " . $requestData['start'] . " ," . $requestData['length'] . "   ";
			$data_query = $this->db->query($query);



			$data = array();
			foreach ($data_query->result() as $row) {

				$id_so = $row->id_so;
				$id_sales_contract = $row->id_sales_contract;
				$code_sales_contract = $row->code_sales_contract;
				$code_so = $row->code_so;
				$nm_customers = $row->nm_customers;

				$date_so = date("d-m-Y", strtotime($row->date_so));
				$link = "tambah_q";

				if($code_sales_contract != null){
					$link = "edit";
					$id_so = $id_sales_contract;
				}

				$kolom_1 = "<a href=\"#\" onclick='show(\"$folder/cform/$link/$id_so/\",\"#main\"); return false;'>$code_sales_contract</a>";
				$kolom_2 = "<a href=\"#\" onclick='show(\"$folder/cform/$link/$id_so/\",\"#main\"); return false;'>$code_so</a>";
				$kolom_3 = "<a href=\"#\" onclick='show(\"$folder/cform/$link/$id_so/\",\"#main\"); return false;'>$date_so</a>";
				$kolom_4 = "<a href=\"#\" onclick='show(\"$folder/cform/$link/$id_so/\",\"#main\"); return false;'>$nm_customers</a>";
				$kolom_5 = "<a href=\"#\" onclick='show(\"$folder/cform/$link/$id_so/\",\"#main\"); return false;'>0</a>";



				$nestedData = array();
				$nestedData[] = $kolom_4;
				$nestedData[] = $kolom_1;
				$nestedData[] = $kolom_2;
				$nestedData[] = $kolom_3;
				$nestedData[] = $kolom_5;
				$data[] = $nestedData;
			}

			$json_data = array(
				"draw"            => intval($requestData['draw']),
				"recordsTotal"    => intval($totalData),
				"recordsFiltered" => intval($totalFiltered),
				"data"            => $data
			);

			echo json_encode($json_data);
		}

		public function data_table2()
		{

			$folder = $this->global['folder'];

			$query = "select y.* from(
				select a.id_so, a.id_sales_contract, a.date_so , a.code_so , b.nm_customers
        from tb_so_hdr a
        left join m_customers b on (a.id_customers = b.id_customers )
        left join m_karyawan c on (a.id_karyawan = c.id_karyawan )
        where a.status_so in('SALE TO INVOICE','SALES ORDER')
				and a.id_sales_contract is null
				order by a.date_so DESC
	) as y";

			$requestData = $this->input->get();
			$totalData = $this->db->query($query)->num_rows();
			$totalFiltered = $totalData;

			if (!empty($requestData['search']['value'])) {
				$cari = [];

                $cari_tmp = $requestData['search']['value'];

                if($cari_tmp){
                    $cari_tmp = strtoupper($cari_tmp);
                }

				foreach (explode(',', $cari_tmp) as $word) {
									$cari[] = "UPPER(y.code_so) like '%$word%'";
									$cari[] = "UPPER(y.nm_customers) like '%$word%'";

				}

				$hasil_cari = implode(" OR ", $cari);

				$query .= " where ( $hasil_cari )";
			}


			$columns = array(
				0 => 'nm_customers',
				1 => 'code_so',
				2 => 'date_so',
			);

			if (!empty($requestData['search']['value'])) {
					$totalFiltered = $this->db->query($query)->num_rows();
			}

			$query .= " ORDER BY " . $columns[$requestData['order'][0]['column']] . "   " . $requestData['order'][0]['dir'] . "  LIMIT " . $requestData['start'] . " ," . $requestData['length'] . "   ";
			$data_query = $this->db->query($query);



			$data = array();
			foreach ($data_query->result() as $row) {

				$id_so = $row->id_so;
				$code_so = $row->code_so;
				$nm_customers = $row->nm_customers;

				$date_so = date("d-m-Y", strtotime($row->date_so));
				$link = "tambah_q";

				$kolom_2 = "<a href=\"#\" onclick='show(\"$folder/cform/$link/$id_so/\",\"#main\"); return false;'>$code_so</a>";
				$kolom_3 = "<a href=\"#\" onclick='show(\"$folder/cform/$link/$id_so/\",\"#main\"); return false;'>$date_so</a>";
				$kolom_4 = "<a href=\"#\" onclick='show(\"$folder/cform/$link/$id_so/\",\"#main\"); return false;'>$nm_customers</a>";



				$nestedData = array();
				$nestedData[] = $kolom_4;
				$nestedData[] = $kolom_2;
				$nestedData[] = $kolom_3;
				$data[] = $nestedData;
			}

			$json_data = array(
				"draw"            => intval($requestData['draw']),
				"recordsTotal"    => intval($totalData),
				"recordsFiltered" => intval($totalFiltered),
				"data"            => $data
			);

			echo json_encode($json_data);
		}


    public function list_so()
    {

        $data = check_role($this->id_menu, 1);
        if (!$data) {
            redirect(base_url(), 'refresh');
        }

        $data = array(
					'folder' => $this->global['folder'],
					'title' => $this->global['title'],
        );

        $this->load->view($this->global['folder'] . '/vformlist_2', $data);
    }

    public function tambah()
    {

        $data = check_role($this->id_menu, 1);
        if (!$data) {
            redirect(base_url(), 'refresh');
        }

        $id_so = $this->uri->segment("4");

        $data = array(
            'folder' => $this->global['folder'],
            'id_so' => $id_so,
            'data_header_so' => $this->mmaster->data_header_so($id_so)->row(),
            'data_detail_so' => $this->mmaster->data_detail_so($id_so),
        );

        $this->Logger->write('Membuka Menu Tambah ' . $this->global['title']);

        $this->load->view($this->global['folder'] . '/vformadd', $data);
    }

    public function tambah_q()
    {

        $data = check_role($this->id_menu, 1);
        if (!$data) {
            redirect(base_url(), 'refresh');
        }

        $id_so = $this->uri->segment("4");

        $data = array(
            'folder' => $this->global['folder'],
            'id_so' => $id_so,
            'data_header_so' => $this->mmaster->data_header_so($id_so)->row(),
            'data_detail_so' => $this->mmaster->data_detail_so($id_so),
        );

        $this->Logger->write('Membuka Menu Tambah ' . $this->global['title']);

        $this->load->view($this->global['folder'] . '/vformadd', $data);
    }

    public function simpan(){
        $data = check_role($this->id_menu, 1);
        if (!$data) {
                redirect(base_url(), 'refresh');
        }

        $this->form_validation->set_rules('id_so', 'id_so', 'trim|required');
        $this->form_validation->set_rules('id_customers', 'id_customers', 'trim|required');
        $this->form_validation->set_rules('f_company[]', 'f_company[]', 'trim');

        if ($this->form_validation->run() == false) {
            $data = array(
                    'sukses' => false,
            );
            $this->load->view('pesan', $data);
        } else {
            $this->db->trans_begin();
            $id_so = $this->input->post('id_so');
            $id_customers = $this->input->post('id_customers');
            $data_f_company = $this->input->post('f_company[]');
            $date_contract = $this->input->post('date_contract');
            $periode = date("Ym", strtotime($date_contract));
            $date_contract = date("Y-m-d", strtotime($date_contract));
            $code_sales_contract = runningnumber_tahun('SC', $periode);
            $jml_barang = $this->input->post('jml_barang');
            $n_amount = str_replace(',', '',$this->input->post('n_amount'));
            $dp_persen = str_replace(',', '',$this->input->post('dp_persen'));
            $dp_nominal = str_replace(',', '',$this->input->post('dp_nominal'));
            $n_sisa = str_replace(',', '',$this->input->post('n_sisa'));
            $lama_cicilan = str_replace(',', '',$this->input->post('lama_cicilan'));
            $jml_cicilan_rp = str_replace(',', '',$this->input->post('jml_cicilan_rp'));

            $nik = $this->input->post('nik');
            $alamat = $this->input->post('alamat');
            $nama_lengkap = $this->input->post('nm_customers');
            $nib = null;
            $npwp = null;
            $f_company = false;

            if($data_f_company != NULL){
                $nama_lengkap = $this->input->post('nama_lengkap');
                $f_company = true;
                $nib = $this->input->post('nib');
                $npwp = $this->input->post('npwp');

            }

            $id_sales_contract = $this->mmaster->insert_header($code_sales_contract, $id_customers, $date_contract,
            $n_amount, $dp_persen, $dp_nominal, $n_sisa, $lama_cicilan, $jml_cicilan_rp, $f_company, $nama_lengkap, $nik, $nib, $npwp, $alamat);

            $this->mmaster->update_header_so($id_so, $id_sales_contract);

            $this->mmaster->update_customer($id_customers, $f_company, $nama_lengkap, $nik, $nib, $npwp, $alamat);

            if($jml_barang){
                for ($x=0; $x <= $jml_barang; $x++) {
                    $id_product = $this->input->post('id_product'.$x);
                    $pilih_product = $this->input->post('pilih_product'.$x);
                    if($id_product && ($pilih_product == 1 || $pilih_product == '1')){
                        $ntot_product_price_netto = $this->input->post('ntot_product_price_netto'.$x);
                        $n_qty = $this->input->post('n_qty'.$x);
                        $this->mmaster->insert_detail_product($id_sales_contract, $id_product, $ntot_product_price_netto, $n_qty);
                    }

                }
            }

            $this->Logger->write('Simpan Data ' . $this->global['title'] . ' Kode : ' . $id_sales_contract);

            if ($this->db->trans_status() === false) {
                    $this->db->trans_rollback();
                    $data = array(
                            'sukses' => false,
                    );
                    $this->load->view('pesan', $data);
            } else {
                    $this->db->trans_commit();
                    $data = array(
                            'sukses' => true,
                            'kode' => $code_sales_contract,
                            'folder' => $this->global['folder'] . '/cform/edit/'.$id_sales_contract.'/f/',
                    );
                    $this->load->view('pesan', $data);
            }

        }


    }


    public function edit()
    {

        $data = check_role($this->id_menu, 1);
        if (!$data) {
            redirect(base_url(), 'refresh');
        }

        $id_sales_contract = $this->uri->segment("4");

        $data = array(
            'folder' => $this->global['folder'],
						'id_sales_contract' => $id_sales_contract,
						'data_header_sc' => $this->mmaster->data_header_sc($id_sales_contract)->row(),
						'data_detail_sc' => $this->mmaster->data_detail_sc($id_sales_contract),
        );

        $this->Logger->write('Membuka Menu Tambah ' . $this->global['title']);

        $this->load->view($this->global['folder'] . '/vformedit', $data);
    }


		public function download_file($id_so = null)
    {
        $id_sales_contract = decrypt_url($this->uri->segment('4'));

            if (!$id_sales_contract) {
                redirect(base_url(), 'refresh');
            }


        $data = array(
            'folder' => $this->global['folder'],
            'title' => $this->global['title'],
						'id_sales_contract' => $id_sales_contract,
						'data_header_sc' => $this->mmaster->data_header_sc($id_sales_contract)->row(),
						'data_detail_sc' => $this->mmaster->data_detail_sc($id_sales_contract),
        );

        $this->Logger->write('Membuka Download Sales Contract Pdf ' . $this->global['title'] . ' id_sales_contract : ' . $id_sales_contract);

        $this->load->library('pdf');
        $this->pdf->setPaper('A4', 'potrait');
        $this->pdf->filename = "Perjanjian.pdf";
        $this->pdf->load_view($this->global['folder'] . '/vformpdf_perjanjian', $data);

    }




}

/* End of file Cform.php */
