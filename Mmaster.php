<?php

defined('BASEPATH') or exit('No direct script access allowed');
class Mmaster extends CI_Model
{


	function data_header_sc($id_sales_contract){
		return $this->db->query("select
		a.id_so,
		a.date_so ,
		a.code_so ,
		a.id_customers,
		b.nm_customers,
		b.f_company,
		b.customers_address,
		a.vcurrency,
		a.id_sales_contract,
		d.code_sales_contract,
		d.f_company,
		d.date_contract,
		d.n_amount,
		d.dp_persen,
		d.dp_nominal,
		d.n_sisa,
		d.lama_cicilan,
		d.jml_cicilan_rp,
		d.nama_lengkap,
		d.nik,
		d.nib,
		d.npwp,
		d.alamat
	from
		tb_so_hdr a
	left join m_customers b on
		(
			a.id_customers = b.id_customers
		)
	left join m_karyawan c on
		(
			a.id_karyawan = c.id_karyawan
		)
	inner join tb_sales_contract_hdr d on
		(
			a.id_sales_contract = d.id_sales_contract
		)
	where
		a.id_sales_contract = '$id_sales_contract'");
	}

	function data_detail_sc($id_sales_contract){
		return $this->db->query("
		select
	a.id_sales_contract ,
	a.id_product,
	b.code_product,
	b.nm_product,
	a.n_qty ,
	a.product_price
from
	tb_sales_contract_product a,
	m_product b
where
	a.id_product = b.id_product
	and a.id_sales_contract = '$id_sales_contract'");
	}




	function data_header_so($id_so){
		return $this->db->query("select a.id_so, a.date_so , a.code_so , a.id_customers, b.nm_customers, b.f_company, b.customers_address,
        a.vcurrency, f_company, b.nama_lengkap, b.nik, b.nib, b.npwp, b.alamat, a.ndp_persen, a.ntenor
		from tb_so_hdr a
		left join m_customers b on (a.id_customers = b.id_customers )
		left join m_karyawan c on (a.id_karyawan = c.id_karyawan )
		where a.id_so = '$id_so'");
	}


	function data_detail_so($id_so){
		return $this->db->query("
		select a.id_so_dtl, a.id_so, a.id_product, b.code_product, b.nm_product, a.nqty, a.product_price, a.ntot_product_price_netto  from tb_so_dtl a, m_product b
				where a.id_product = b.id_product
				and a.id_so = '$id_so'");
	}

	function insert_header( $code_sales_contract, $id_customers, $date_contract,
	$n_amount, $dp_persen, $dp_nominal, $n_sisa, $lama_cicilan, $jml_cicilan_rp, $f_company, $nama_lengkap, $nik, $nib, $npwp, $alamat){

		$data = array(
			'code_sales_contract' => $code_sales_contract,
			'id_customers' => $id_customers,
			'date_contract' => $date_contract,
			'n_amount' => $n_amount,
			'dp_persen' => $dp_persen,
			'dp_nominal' => $dp_nominal,
			'n_sisa' => $n_sisa,
			'lama_cicilan' => $lama_cicilan,
			'jml_cicilan_rp' => $jml_cicilan_rp,
			'f_company' => $f_company,
			'nama_lengkap' => $nama_lengkap,
			'nik' => $nik,
			'nib' => $nib,
			'npwp' => $npwp,
			'alamat' => $alamat
		);


		$this->db->insert('tb_sales_contract_hdr', $data);
		return $this->db->insert_id();

	}

	function update_header_so($id_so, $id_sales_contract){

		$data = array(
			'id_sales_contract' => $id_sales_contract
		);


		$this->db->where('id_so', $id_so);

		$this->db->update('tb_so_hdr', $data);


	}


	function insert_detail_product($id_sales_contract, $id_product, $ntot_product_price_netto, $n_qty){

		$data = array(
			'id_sales_contract' => $id_sales_contract,
			'id_product' => $id_product,
			'product_price' => $ntot_product_price_netto,
			'n_qty' => $n_qty
		);


		$this->db->insert('tb_sales_contract_product', $data);
		return $this->db->insert_id();

	}


    function update_customer($id_customers, $f_company, $nama_lengkap, $nik, $nib, $npwp, $alamat){
        $data = array(
			'f_company' => $f_company,
            'nama_lengkap' => $nama_lengkap,
            'nik' => $nik,
            'nib' => $nib,
            'npwp' => $npwp,
            'alamat' => $alamat
		);


		$this->db->where('id_customers', $id_customers);

		$this->db->update('m_customers', $data);
    }

}

/* End of file Mmaster.php */
