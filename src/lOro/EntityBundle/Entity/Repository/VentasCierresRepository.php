<?php

namespace lOro\EntityBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;


/**
 * TransferenciasRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class VentasCierresRepository extends EntityRepository
{
    
    
  public function getCierresPorMesAnioEnCurso($arregloFiltrosCierres,$arrayIdProvPermitidos = NULL) {
    $config = $this->getEntityManager()->getConfiguration();
    $config->addCustomNumericFunction('MONTH', 'DoctrineExtensions\Query\Mysql\Month');
    $config->addCustomNumericFunction('YEAR', 'DoctrineExtensions\Query\Mysql\Year');
        
      if($arrayIdProvPermitidos != NULL):
        $q = 'SELECT vc
                 FROM lOroEntityBundle:VentasCierres vc
                 WHERE vc.tipoCierre = :tipoCierre
                 AND vc.proveedorCierre IN ('.$arrayIdProvPermitidos.')
                 ORDER BY vc.id DESC';
      else:
        $q = 'SELECT vc
                 FROM lOroEntityBundle:VentasCierres vc
                 WHERE MONTH(vc.feVenta) = MONTH(CURRENT_TIMESTAMP())
                 AND YEAR(vc.feVenta) = YEAR(CURRENT_TIMESTAMP())
                 AND vc.tipoCierre = :tipoCierre
                 ORDER BY vc.id DESC';
      endif;

      $query = $this->getEntityManager()
                    ->createQuery($q)->setParameter('tipoCierre',$arregloFiltrosCierres['tipoCierre']);

      return $query->getResult();     
  }

    public function traerCierresDelDiaProveedores() {
      $feActual = new \DateTime('now');
      $formatFeActual = $feActual->format('Y-m-d');

      $conn = $this->getEntityManager()->getConnection();

      $query = "SELECT *
                FROM v_index_cierres_del_dia AS vcd
                WHERE vcd.raw_fe_venta = '$formatFeActual';";
      
      $stmt = $conn->executeQuery($query);
        
      return $stmt->fetchAll();        
    }
    
    public function findVentasCierresPorFecha($proveedorId,$feDesde,$feHasta){
      $conn = $this->getEntityManager()->getConnection();

      $query = "SELECT vc.id AS id_cierre,
                       vc.fe_venta,
                       p.nb_proveedor,
                       vc.cantidad_total_venta,
                       vc.monto_bs_cierre_por_gramo,
                       vc.monto_bs_cierre
                FROM ventas_cierres AS vc
                JOIN proveedores AS p ON (p.id = vc.proveedor_id)
                WHERE vc.tipo_cierre = 'proveedor'
                AND vc.fe_venta BETWEEN '$feDesde' AND '$feHasta'
                AND p.id = $proveedorId;";
      
      $stmt = $conn->executeQuery($query);
            
            
      return $stmt->fetchAll();         
    }
        
    public function traerCierresPorFechasProveedor($idProveedor = null,$feInicial,$feFinal) {
      $conn = $this->getEntityManager()->getConnection();

      $feDesde = $feInicial->format('Y-m-d');
      $feHasta = $feFinal->format('Y-m-d');
      
      $sqlProveedor = ($idProveedor != null ? "AND vc.proveedor_id = $idProveedor" : "");
      
      $queryAnualizado = "SELECT *
                          FROM ventas_cierres AS vc
                          JOIN proveedores AS p ON (p.id = vc.proveedor_id)
                          WHERE vc.fe_venta BETWEEN '$feDesde'
                          AND '$feHasta'
                          $sqlProveedor
                          ORDER BY vc.fe_venta DESC;";
      
      $stmt = $conn->executeQuery($queryAnualizado);
            
            
      return $stmt->fetchAll();
    }
    
    public function traerSiguienteCierreInactivo($idCierreActivo,$feVenta,$tipoCierre = 'proveedor') {
        return $this->getEntityManager()
            ->createQuery(
                'SELECT vc FROM lOroEntityBundle:VentasCierres vc
                 WHERE vc.id != :id
                 AND   vc.feVenta >= :feVenta
                 AND   vc.tipoCierre = :tipoCierre
                 AND   vc.gramosCerradosRestantes != :gramosCerradosRestantes 
                 AND   vc.estatus = :estatus 
                 
                 ORDER BY vc.feVenta ASC'
            )
            ->setParameters(array(':id' => $idCierreActivo,
                                  ':feVenta' => $feVenta,
                                  ':tipoCierre' => $tipoCierre,
                                  ':gramosCerradosRestantes' => '0.00',
                                  ':estatus' => 'I'))
            ->getResult();
        //AND   vc.id NOT IN (113,114,115,116,117,118,119)
    }
    
    public function traerSiguienteCierrePiezasInactivo($idCierreActivo,$feVenta,$tipoCierre = 'proveedor') {
        return $this->getEntityManager()
            ->createQuery(
                'SELECT vc FROM lOroEntityBundle:VentasCierres vc
                 WHERE vc.id != :id
                 AND   vc.feVenta >= :feVenta
                 AND   vc.tipoCierre = :tipoCierre
                 AND   vc.gramosCerradosRestantesPiezas != :gramosCerradosRestantes 
                 AND   vc.estatus = :estatus 
                 
                 ORDER BY vc.feVenta ASC'
            )
            ->setParameters(array(':id' => $idCierreActivo,
                                  ':feVenta' => $feVenta,
                                  ':tipoCierre' => $tipoCierre,
                                  ':gramosCerradosRestantes' => '0.00',
                                  ':estatus' => 'I'))
            ->getResult();
        //AND   vc.id NOT IN (113,114,115,116,117,118,119)
    }    
    
    public function traerSiguienteCierreInactivoPorProveedor($idCierreActivo,$feVenta,$proveedor) {
        return $this->getEntityManager()
            ->createQuery(
                'SELECT vc FROM lOroEntityBundle:VentasCierres vc
                 WHERE vc.id != :id
                 AND   vc.feVenta >= :feVenta
                 AND   vc.tipoCierre = :tipoCierre
                 AND   vc.gramosCerradosRestantes != :gramosCerradosRestantes 
                 AND   vc.estatus = :estatus 
                 AND   vc.proveedorCierre = :proveedor
                 
                 ORDER BY vc.feVenta ASC'
            )
            ->setParameters(array(':id' => $idCierreActivo,
                                  ':feVenta' => $feVenta,
                                  ':tipoCierre' => 'proveedor',
                                  ':gramosCerradosRestantes' => '0.00',
                                  ':estatus' => 'I',
                                  ':proveedor' => $proveedor))
            ->getResult();
        
        //AND   vc.id NOT IN (113,114,115,116,117,118,119)
                 
    }    
    
    public function traerEntregasRestantesPorRelacionar() {
        return $this->getEntityManager()
            ->createQuery(
                'SELECT e FROM lOroEntityBundle:Entregas e
                 WHERE e.restantePorRelacion > :restantePorRelacion
                 ORDER BY e.feEntrega ASC'
            )
            ->setParameters(array(':restantePorRelacion' => '0.00'))
            ->getResult();
    }    
    
    public function traerEntregasProveedorRestantesPorRelacionar($proveedor) {
        return $this->getEntityManager()
            ->createQuery(
                'SELECT e FROM lOroEntityBundle:Entregas e
                 WHERE e.restantePorRelacion > :restantePorRelacion
                 AND e.proveedor = :proveedor
                 ORDER BY e.feEntrega ASC'
            )
            ->setParameters(array(':restantePorRelacion' => '0.00','proveedor' => $proveedor))
            ->getResult();
    }   
    
    public function findAllCierresMayoresA($id) {
        return $this->getEntityManager()
            ->createQuery(
                'SELECT e FROM lOroEntityBundle:VentasCierres e 
                 WHERE e.id >= :id'
            )
            ->setParameter(':id',$id)
            ->getResult();        
    }
    
    public function buscarCierresPorId($valorBusqueda,$lugar)
    {
        
      if($lugar == 'proveedores'):
        $tipoCierre = 'proveedor';   
      else:
        $tipoCierre = 'hc';    
      endif;
      
      $valorBusqueda = ($valorBusqueda ? $valorBusqueda : '');
      
      $qb = $this->createQueryBuilder('u');
      $result = $qb ->select('u')
                    ->where('u.tipoCierre',$tipoCierre)
                    ->where(
                      $qb->expr()->like('u.id',$qb->expr()->literal('%'. $valorBusqueda . '%'))
                    )
                    ->orderBy('u.id','DESC')
       ->getQuery()   
       ->getResult();
       
      return $result;
    }
    
    public function buscarCierresPorFechas($feInicio,$feFinal,$tipoCierre) 
    {
      return $this->getEntityManager()
            ->createQuery(
                'SELECT e
                 FROM lOroEntityBundle:VentasCierres e
                 WHERE (e.feVenta
                 BETWEEN :feInicio AND :feFinal)
                 AND  e.tipoCierre = :tipoCierre
                 ORDER BY e.id DESC'
            )
            ->setParameter('feInicio', new \ DateTime($feInicio))
            ->setParameter('feFinal', new \ DateTime($feFinal))
            ->setParameter('tipoCierre', $tipoCierre)
            ->getResult();
      }
      
      public function buscarCierresPorProveedor($idProveedor,$tipoCierre) {
          
      return $this->getEntityManager()
            ->createQuery(
                'SELECT e
                 FROM lOroEntityBundle:VentasCierres e
                 WHERE e.proveedorCierre = :idProveedor
                 AND  e.tipoCierre = :tipoCierre
                 ORDER BY e.id DESC'
            )
            ->setParameter('idProveedor', $idProveedor)
            ->setParameter('tipoCierre', $tipoCierre)
            ->getResult();          
      }  
      
      /* Busca todas las entregas que no se encuentren en la tabla "cierres_hc_entregas"
       * o que su restante_por_relacion sea menor al peso_puro_entrega ya que
       * esto significa que todavia queda entrega para suplir otro cierre,
       * estas entregas seran relacionadas con el cierre que va a ser registrado
       */
      public function buscarEntregasNoRelacionadasCierresHc() {
          
      return $this->getEntityManager()
            ->createQuery(
                'SELECT e
                 FROM lOroEntityBundle:Entregas e
                 WHERE e.restantePorRelacion <> 0
                 ORDER BY e.feEntrega'
            )
            ->getResult();          
      }      
}

