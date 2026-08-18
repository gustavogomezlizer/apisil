function cargarNegociosCombobox(cmb)
{
    axios.get("/catalogos/getNegocios", {
        responseType: 'json'
    })
    .then(function(data) {

        var datos = data["data"];
        var options = [];

        if(datos.length > 0)
        {
            datos.forEach(function (item) {
                var option = "<option value='" + item.id + "' title='" + item.nombre + "'>" + item.nombre + "</option>";
                options.push(option);
            });

            cmb.html(options);
            cmb.selectpicker('refresh');
        }
    })
    .catch(function(err) {
        alert(err)
    });
}

function cargarSucursalesCombobox(cmb)
{
    axios.get("/catalogos/getSucursales", {
        responseType: 'json'
    })
    .then(function(data) {

        var datos = data["data"];
        var options = [];			

        if(datos.length > 0)
        {
            datos.forEach(function (item) {
                var option = "<option value='" + item.id + "' title='" + item.sucursal + "'>" + item.sucursal + "</option>";
                options.push(option);
            });

            cmb.html(options);
            cmb.selectpicker('refresh');
        }
    })
    .catch(function(err) {
        alert(err)
    });
}

function cargarTipoVehiculoCombobox(cmb)
{
    axios.get("/catalogos/getTiposVehiculo", {
        responseType: 'json'
    })
    .then(function(data) {

        var datos = data["data"];

        if(datos.length > 0)
        {
            cmb.empty();
            cmb.append("<option value='0'>[SELECCIONE UN TIPO DE VEHICULO]</option>");
            datos.forEach(function (item) {
                var option = "<option value='" + item.id + "'>" + item.nombre + "</option>";
                cmb.append(option);
            });
        }
    })
    .catch(function(err) {
        alert(err)
    });
}

function cargarAniosCombobox(cmb)
{
    axios.get("/catalogos/getAnios", {
        responseType: 'json'
    })
    .then(function(data) {

        var datos = data["data"];

        if(datos.length > 0)
        {
            cmb.empty();
            cmb.append("<option value='0'>[SELECCIONE UN AÑO]</option>");
            datos.forEach(function (item) {
                var option = "<option value='" + item.id + "'>" + item.nombre + "</option>";
                cmb.append(option);
            });
        }
    })
    .catch(function(err) {
        alert(err)
    });
}

function cargarMarcasCombobox(cmb)
{
    axios.get("/catalogos/getMarcas", {
        responseType: 'json'
    })
    .then(function(data) {

        var datos = data["data"];

        if(datos.length > 0)
        {
            cmb.empty();
            cmb.append("<option value='0'>[SELECCIONE UNA MARCA]</option>");
            datos.forEach(function (item) {
                var option = "<option value='" + item.id + "'>" + item.nombre + "</option>";
                cmb.append(option);
            });
        }
    })
    .catch(function(err) {
        alert(err)
    });
}

function cargarModelosCombobox(cmb, pIdMarca, pIdModelo)
{
    axios.get("/catalogos/getModelos/" + pIdMarca, {
        responseType: 'json'
    })
    .then(function(data) {

        var datos = data["data"];
        
        cmb.empty();
        cmb.append("<option value='0'>[SELECCIONE UN MODELO]</option>");

        if(datos.length > 0)
        {            
            datos.forEach(function (item) {
                var option = "<option value='" + item.id + "'" + (pIdModelo == item.id ? 'selected' : '') + ">" + item.nombre + "</option>";
                cmb.append(option);
            });
        }
    })
    .catch(function(err) {
        alert(err)
    });
}

function cargarColoresCombobox(cmb)
{
    axios.get("/catalogos/getColores", {
        responseType: 'json'
    })
    .then(function(data) {

        var datos = data["data"];
        
        cmb.empty();
        cmb.append("<option value='0'>[SELECCIONE UN COLOR]</option>");

        if(datos.length > 0)
        {            
            datos.forEach(function (item) {
                var option = "<option value='" + item.id + "'>" + item.nombre + "</option>";
                cmb.append(option);
            });
        }
    })
    .catch(function(err) {
        alert(err)
    });
}

function cargarDepartamentosCombobox(cmb)
{
    axios.get("/catalogos/getDepartamentos", {
        responseType: 'json'
    })
    .then(function(data) {

        var datos = data["data"];
        
        cmb.empty();
        cmb.append("<option value='0'>[SELECCIONE UN DEPARTAMENTO]</option>");

        if(datos.length > 0)
        {            
            datos.forEach(function (item) {
                var option = "<option value='" + item.id + "'>" + item.nombre + "</option>";
                cmb.append(option);
            });
        }
    })
    .catch(function(err) {
        alert(err)
    });
}

function cargarUnidadesCombobox(cmb)
{
    axios.get("/controlunidad/getUnidades", {
        responseType: 'json'
    })
    .then(function(data) {

        var datos = data["data"];
        var options = [];

        options.push("<option value='0' title='[SELECCIONE UNA UNIDAD]'>[SELECCIONE UNA UNIDAD]</option>");

        if(datos.length > 0)
        {
            datos.forEach(function (item) {
                var option = "<option value='" + item.id + "' title='" + item.numeroeconomico + " - " + item.modelo + "'>" + item.numeroeconomico + " - " + item.modelo + "</option>";
                options.push(option);
            });

            cmb.html(options);
            cmb.selectpicker('refresh');
        }
    })
    .catch(function(err) {
        alert(err)
    });
}

function cargarNivelGasolinaCombobox(cmb)
{
    axios.get("/catalogos/getNivelGasolina", {
        responseType: 'json'
    })
    .then(function(data) {

        var datos = data["data"];
        
        cmb.empty();
        cmb.append("<option value='0'>[SELECCIONE UN NIVEL DE GASOLINA]</option>");

        if(datos.length > 0)
        {            
            datos.forEach(function (item) {
                var option = "<option value='" + item.niveldecimales + "'>" + item.nivelfraccion + "</option>";
                cmb.append(option);
            });
        }
    })
    .catch(function(err) {
        alert(err)
    });
}

function cargarPuestosCombobox(cmb)
{
    axios.get("/catalogos/getPuestos", {
        responseType: 'json'
    })
    .then(function(data) {

        var datos = data["data"];
        
        cmb.empty();
        cmb.append("<option value='0'>[SELECCIONE UN PUESTO]</option>");

        if(datos.length > 0)
        {            
            datos.forEach(function (item) {
                var option = "<option value='" + item.id + "'>" + item.nombre + "</option>";
                cmb.append(option);
            });
        }
    })
    .catch(function(err) {
        alert(err)
    });
}

function cargarEstadoCivilCombobox(cmb)
{
    axios.get("/catalogos/getEstadoCivil", {
        responseType: 'json'
    })
    .then(function(data) {

        var datos = data["data"];
        
        cmb.empty();
        cmb.append("<option value='0'>[SELECCIONE UN ESTADO CIVIL]</option>");

        if(datos.length > 0)
        {            
            datos.forEach(function (item) {
                var option = "<option value='" + item.id + "'>" + item.nombre + "</option>";
                cmb.append(option);
            });
        }
    })
    .catch(function(err) {
        alert(err)
    });
}

function cargarTiposLicenciaCombobox(cmb)
{
    axios.get("/catalogos/getTiposLicencia", {
        responseType: 'json'
    })
    .then(function(data) {

        var datos = data["data"];
        
        cmb.empty();
        cmb.append("<option value='0'>[SELECCIONE UN TIPO DE LICENCIA]</option>");

        if(datos.length > 0)
        {            
            datos.forEach(function (item) {
                var option = "<option value='" + item.id + "'>" + item.nombre + "</option>";
                cmb.append(option);
            });
        }
    })
    .catch(function(err) {
        alert(err)
    });
}

function cargarProveedoresCombobox(cmb)
{
    axios.get("/catalogos/getProveedores", {
        responseType: 'json'
    })
    .then(function(data) {

        var datos = data["data"];
        var options = [];

        options.push("<option value='0' title='[SELECCIONE UN PROVEEDOR]'>[SELECCIONE UN PROVEEDOR]</option>");

        if(datos.length > 0)
        {
            datos.forEach(function (item) {
                var option = "<option value='" + item.id + "' title='" + item.razonsocial + "'>" + item.razonsocial + "</option>";
                options.push(option);
            });

            cmb.html(options);
            cmb.selectpicker('refresh');
        }
    })
    .catch(function(err) {
        alert(err)
    });
}

function cargarConceptosMentenimientoCombobox(cmb)
{
    axios.get("/catalogos/getConceptosMantenimiento", {
        responseType: 'json'
    })
    .then(function(data) {

        var datos = data["data"];
        var options = [];

        options.push("<option value='0' title='[SELECCIONE UN CONCEPTO]'>[SELECCIONE UN CONCEPTO]</option>");

        if(datos.length > 0)
        {
            datos.forEach(function (item) {
                var option = "<option value='" + item.id + "' title='" + item.nombre + "'>" + item.nombre + "</option>";
                options.push(option);
            });

            cmb.html(options);
            cmb.selectpicker('refresh');
        }
    })
    .catch(function(err) {
        alert(err)
    });
}