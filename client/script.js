const toggleManualControls = () => {
    const manualControls = document.querySelector(".manual-controls");
    manualControls.classList.toggle("active");
}

const toggleAutoControls = () => {
    const autoControls = document.querySelector(".auto-controls");
    autoControls.classList.toggle("active");
}
const togglePresetControls = () => {
    const presetControls = document.querySelector(".preset-controls");
    presetControls.classList.toggle("active");
}
const capitalizeFirstLetter = (str) => {
  if (!str) return '';
  return str.charAt(0).toUpperCase() + str.slice(1);
};
const loadPresets = async () => {
  const presets = await getPresets();
  if (!presets) return;
  const container = document.querySelector(".presets-container");
  Array.from(container.children).forEach((el) => el.remove());
  const grouped = presets.reduce((acc, preset) => {
    if (!acc[preset.preset_id]) {
      acc[preset.preset_id] = [];
    }
    acc[preset.preset_id].push(preset);
    return acc;
  }, {});

  for (const presetId in grouped) {
    if (Object.hasOwnProperty.call(grouped, presetId)) {
      let group = grouped[presetId];

      group.sort((a, b) => a.use_order - b.use_order);

      const name = group[0]?.name || "Unnamed";

      const simplified = group.map(({ type, time_times }) => ({ type, time_times }));

      createPresetItem(name, simplified, presetId);
    }
  }
};


const handlePresetButton = (e) => {
    const presetItem = e.currentTarget;
    document.querySelectorAll(".preset-item.active").forEach(el => {if(el!=presetItem)el.classList.remove("active");});
    presetItem.classList.toggle("active");
}
const handlePresetBut = (which, someData) => {
    loadPresets()
    document.querySelectorAll(".all-container, .header").forEach(el => {el.classList.add("blurred");});
    const popup = document.querySelector(`.${which === "new" || which==="edit" ? "create-new-preset-container" : (which=="step" ? "step-type-selection"  : "preset-selection")}`);
    popup.style.display= popup.style.display === "grid" ? "none" : "grid";
    setTimeout(() => {
        popup.classList.add("active");
    }, 1);
    if(which==="step") document.querySelector('.create-new-preset-container').classList.add("blurred")
    if(which==="new") {
      document.querySelector('.create-new-preset-container').attributes.action="new"
      document.querySelector('.preset-selection').classList.add("blurred")
      document.getElementById("new-edit-preset-container-title").innerHTML="Create preset"
    }
    if(which==="edit"){
      document.querySelector('.create-new-preset-container').attributes.presetId=someData.presetId;
      document.querySelector('.create-new-preset-container').attributes.action="edit"
      document.getElementById("new-edit-preset-container-title").innerHTML="Edit preset"
      document.getElementById("new-preset-name-input").value=someData.name;
      someData.arr.forEach((el)=>{
        addStep("edit",el);
      })
    } 
}
const handleClosePresetSelection = (which) =>{
    loadPresets()
    const popup = document.querySelector(`.${which === "new" || which==="edit"  ? "create-new-preset-container" : (which=="step" ? "step-type-selection"  : "preset-selection")}`);
    popup.classList.remove("active");
    setTimeout(() => {
        popup.style.display= "none";
    }, 100);
    if(which === "list")document.querySelectorAll(".preset-item.active").forEach(el => {el.classList.remove("active");});
    if(which=='list')document.querySelectorAll(".all-container, .header").forEach(el => {el.classList.remove("blurred");});
    if(which==="step") {
        document.getElementById("new-step-time").value = null
        document.querySelector('.create-new-preset-container').classList.remove("blurred")
        const prev = document.querySelectorAll('.step-type.selected')
        if(prev)prev.forEach((el)=>el.classList.remove("selected"))
    }
    if(which==="new"){ 
        document.querySelector('.preset-selection').classList.remove("blurred")

    }
    if(which==="edit"||which==="new"){
      document.getElementById("new-preset-name-input").value='';
        const container = document.querySelector(".preset-steps");
        Array.from(container.children).forEach((el) => el.remove());
    }
}

const handleNewPresetBut = () =>{
}


const handleCheckHover = (e) =>{
    const wrapper = e.target.parentElement.parentElement.parentElement
    wrapper.classList.add("checked")
}
const handleCheckHoverLeave = (e) =>{
    const wrapper = e.target.parentElement.parentElement.parentElement
    wrapper.classList.remove("checked")
}
const handleStepDeleteHover =(e) =>{
    const wrapper = e.target.parentElement
    wrapper.classList.add("delete")
}
const handleStepDeleteHoverLeave = (e) =>{
    const wrapper = e.target.parentElement
    wrapper.classList.remove("delete")
}
const handleSelectPreset = (e) =>{
  console.log("SELECTEDS " + e.target.parentElement.parentElement.parentElement.attributes.presetId)
    e.stopPropagation();
    handleClosePresetSelection('list');
    selectPreset(e.target.parentElement.parentElement.parentElement.attributes.presetId)
}
const handleEditHover = (e) =>{
    const wrapper = e.target.parentElement.parentElement.parentElement
    wrapper.classList.add("edit")
}
const handleEditHoverLeave = (e) =>{
    const wrapper = e.target.parentElement.parentElement.parentElement
    wrapper.classList.remove("edit")
}
const handleLogoutButtonHover =(e) =>{
    const wrapper = e.target.parentElement
    wrapper.classList.add("logout")
}
const handleLogoutButtonHoverLeave = (e) =>{
    const wrapper = e.target.parentElement
    wrapper.classList.remove("logout")
}
function createArrowSVG(fillColor = '#ffffff', width = 40, height = 40) {
  const svgNS = "http://www.w3.org/2000/svg";
  const svg = document.createElementNS(svgNS, "svg");
  svg.setAttribute("class", "arrow");
  svg.setAttribute("width", width);
  svg.setAttribute("height", height);
  svg.setAttribute("fill", fillColor);
  svg.setAttribute("viewBox", "0 0 24 24");
  svg.setAttribute("xmlns", svgNS);
  const path = document.createElementNS(svgNS, "path");
  path.setAttribute("d", "m4.594 8.912 6.553 7.646a1.126 1.126 0 0 0 1.708 0l6.552-7.646c.625-.73.107-1.857-.854-1.857H5.447c-.961 0-1.48 1.127-.853 1.857Z");
  svg.appendChild(path);
  return svg;
}
function createCheckSVG(fillColor = '#ffffff', width = 40, height = 40) {
  const svgNS = "http://www.w3.org/2000/svg";
  const svg = document.createElementNS(svgNS, "svg");
  svg.setAttribute("class", "check");
  svg.setAttribute("width", width);
  svg.setAttribute("height", height);
  svg.setAttribute("fill", fillColor);
  svg.setAttribute("viewBox", "0 0 24 24");
  svg.setAttribute("xmlns", svgNS);
  const path = document.createElementNS(svgNS, "path");
  path.setAttribute("d", "M20.664 5.253a1 1 0 0 1 .083 1.411l-10.666 12a1 1 0 0 1-1.495 0l-5.333-6a1 1 0 0 1 1.494-1.328l4.586 5.159 9.92-11.16a1 1 0 0 1 1.411-.082Z");
  svg.appendChild(path);
  return svg;
}
function createEditSVG(fillColor = 'none', width = 40, height = 40) {
  const svgNS = "http://www.w3.org/2000/svg";
  const svg = document.createElementNS(svgNS, "svg");
  svg.setAttribute("width", width);
  svg.setAttribute("height", height);
  svg.setAttribute("fill", fillColor);
  svg.setAttribute("stroke", "currentColor");
  svg.setAttribute("stroke-linecap", "round");
  svg.setAttribute("stroke-linejoin", "round");
  svg.setAttribute("stroke-width", "1.5");
  svg.setAttribute("viewBox", "0 0 24 24");
  svg.setAttribute("xmlns", svgNS);
  const path1 = document.createElementNS(svgNS, "path");
  path1.setAttribute("d", "M3.5 21h18");
  svg.appendChild(path1);
  const path2 = document.createElementNS(svgNS, "path");
  path2.setAttribute("d", "M5.5 13.36V17h3.659L19.5 6.654 15.848 3 5.5 13.36Z");
  svg.appendChild(path2);
  return svg;
}

const createDeleteSVG = () => {
    const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
    svg.setAttribute("width", "40");
    svg.setAttribute("height", "40");
    svg.setAttribute("fill", "#ffffff");
    svg.setAttribute("viewBox", "0 0 24 24");
    svg.setAttribute("xmlns", "http://www.w3.org/2000/svg");
    svg.innerHTML = `<path d="M4 8h16v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V8Zm2 2v10h12V10H6Zm3 2h2v6H9v-6Zm4 0h2v6h-2v-6ZM7 5V3a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v2h5v2H2V5h5Zm2-1v1h6V4H9Z"></path>`;
    return svg;
};


const useCommand = (command) => {

}

const throwError = (txt) => {
    document.querySelector(".error-text").innerHTML = txt;
    err=  document.querySelector(".error")
    err.classList.add("active");
    setTimeout(() => {  
        err.classList.remove("active");
    }, 2000);
}/*
document.querySelector(".presets-container").appendChild(createPresetItem("Preset 3", {
  washTime: "30s",
  uvTime: "30s",
  dryTime: "20s"
}));
*/
window.onload = () => {
    const token = localStorage.getItem('token') || '';

    fetch('../server/check_JWT.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer ' + token
        },
    })
    .then(res => res.text())
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (!data.valid) {
                window.location = '/opticlean/client/login.html';
                return;
            }

            // ✅ Logged in: Update user display & click behavior
            const userElem = document.getElementById("user");
            userElem.innerHTML = data.username;
            const logoutButton = document.querySelector(".log-in-but > .log-out-but");
            logoutButton.onclick = () => {
                // Optional: Add a logout prompt or menu
                localStorage.removeItem("token");
                window.location.reload(); // Or redirect to login
            };

        } catch (e) {
            console.error("Invalid JSON from check_JWT:", e);
        }
    })
    .catch(err => {
        console.error("JWT Check failed:", err);
    });

    setCamera();

    updateSelectedPresetName()
    //temp
    updateTemperature();
    setInterval(updateTemperature, 500);
};


const setCamera = () => {
    const token = localStorage.getItem('token');
    fetch("../server/adress.php", {
        method: 'GET',
        headers: {
            'Authorization': 'Bearer ' + token
        }
    })
    .then(res => {
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json();
    })
    .then(data => {
        console.log("Camera IP Response:", data);
        document.getElementById('live-camera').src = `http://${data.ip}:8080/?action=stream`;
    })
    .catch(err => {
        console.error("Error loading camera feed:", err);
    });
};


  new Sortable(document.querySelector('.preset-steps'), {
    animation: 150, 
    ghostClass: 'dragging', 
  });
  document.querySelectorAll('.preset-steps >  svg').forEach(svg => {
  svg.addEventListener('mousedown', (e) => {
    e.stopPropagation(); 
  });
});

const selectStepType = (e) =>{
    console.log(e)
    const prev = document.querySelectorAll('.step-type.selected')
    if(prev)prev.forEach((el)=>el.classList.remove("selected"))
    e.target.classList.add("selected");
}
const addStep = (who, data) => {
      let stepName, timeValue
  if(who==="UI" || !who){
    const selected = document.querySelector('.step-type.selected');
    const timeInput = document.getElementById('new-step-time');
    if (!selected) {
        throwError('Select a step type first.');
        return;
    }

     timeValue = parseInt(timeInput.value);
    if (isNaN(timeValue)) {
        throwError('Please enter a valid time.');
        return;
    }

    if (timeValue < 1 || timeValue > 300) {
        throwError('Time must be between 1 and 300 seconds.');
        return;
    }

    stepName = selected.innerHTML.trim();
  }else if(who==="edit"){
    stepName = data.type;
    timeValue = data.time_times
  }
    const stepContainer = document.createElement('div');
    stepContainer.className = 'preset-step';

    const deleteSVG = createDeleteSVG();
    deleteSVG.onclick = handleDeleteStep;
    deleteSVG.onmouseover = handleStepDeleteHover;
    deleteSVG.onmouseleave = handleStepDeleteHoverLeave;

    const stepNameElem = document.createElement('h4');
    stepNameElem.textContent = stepName;

    const stepTimeElem = document.createElement('p');
    stepTimeElem.textContent = `${timeValue}s/times`;
    stepTimeElem.attributes.number = timeValue
    stepContainer.append(deleteSVG, stepNameElem, stepTimeElem);

    document.querySelector('.preset-steps').appendChild(stepContainer);
    handleClosePresetSelection('step');
};


const handleDeleteStep =async(e) =>{
    e.stopPropagation();
    const stepContainer = e.target.closest('.preset-step');
    await stepContainer.remove();
}
const deleteOldPreset = () => { 
  const presetId = document.querySelector('.create-new-preset-container').attributes.presetId;
  console.log("removing - id: " + presetId) 
  if (!presetId) return; 
  const jwtToken = localStorage.getItem('token'); 
  if (!jwtToken) return; 
  fetch('../server/remove_preset.php', { 
    method: 'POST', 
    headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${jwtToken}` }, 
    body: JSON.stringify({ preset_id: presetId }) 
  })
  .then(response => { 
    if (!response.ok) throw new Error('Failed to delete preset'); 
    return response.json(); 
  })
  .then(data => { 
    console.log(data)
  })
  .catch(error => { 
    throwError(error.message); 
  });
};

const createNewPreset = async () =>{
  if(document.querySelector('.create-new-preset-container').attributes.action==="edit") await deleteOldPreset();
    let arr  = [], i = 0
    const steps = document.querySelectorAll('.preset-step');
    const name = document.getElementById("new-preset-name-input").value;
    steps.forEach(step => {
        const type = step.children[1].innerHTML;
        const time_times =  step.children[2].attributes.number
        console.log("Time/times IS "+time_times)
        arr.push({use_order:++i,type,time_times,name})
    });
    console.log(arr)
      fetch('../server/make_preset.php', {  // <-- Replace with your actual API endpoint
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': 'Bearer ' + localStorage.getItem('token') // Assuming you store JWT token in localStorage
    },
    body: JSON.stringify(arr)
    })
    .then(async response => {
        console.log(response)
    const data = await response.json();
    if (!response.ok) {
        throwError(data.error || 'Unknown error');
    }
    return data;
    })
  .then(data => {
    console.log('Preset created with preset_id:', data);
    handleClosePresetSelection('new');
  })
  .catch(err => {
    console.error('Error creating preset:', err.message);
  });
}

const getPresets = async () => {
  try {
    const token = localStorage.getItem('token'); 
    const response = await fetch('../server/get_presets.php', {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    });
    console.log(response)
    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(errorData.error || 'Failed to fetch presets');
    }
    const data = await response.json();
    if (data.success) {
      return data.presets;  
    } else {
      throw new Error(data.error || 'Failed to get presets');
    }

  } catch (error) {
    throwError(error);
    return null;  
  }
};

const createPresetItem = (name, props, presetId) => {
  const el = (tag, cls, html) => {
    const e = document.createElement(tag);
    if (cls) e.className = cls;
    if (html) e.innerHTML = html;
    return e;
  };
  const container = el("div", "preset-item");
  container.onclick = handlePresetButton;
  const titleDiv = el("div", "title");
  titleDiv.appendChild(el("h3", null, name));
  const svgContainer = el("div", "preset-svg-container");
  const checkSVG = createCheckSVG();
  checkSVG.onmouseover = handleCheckHover;
  checkSVG.onmouseleave = handleCheckHoverLeave;
  checkSVG.onclick = handleSelectPreset;
  const arrowSVG = createArrowSVG();
  const editSVG = createEditSVG();
  editSVG.onmouseover = handleEditHover;
  editSVG.onmouseleave = handleEditHoverLeave;
  editSVG.onclick =handleEditPreset;
  svgContainer.append(editSVG,checkSVG, arrowSVG);
  titleDiv.appendChild(svgContainer);
  const propsDiv = el("div", "properties");
  container.attributes.data = props
  container.attributes.name = name
   container.attributes.presetId = presetId
  let i = 1
  props.forEach((elm)=>{
    propsDiv.appendChild(el("p", null, `${i}. ${capitalizeFirstLetter(elm.type)} ${elm.time_times}${elm.type==="spray"?' times' : 's'}`));i++;
  })
  container.append(titleDiv, propsDiv);
  document.querySelector(".presets-container").appendChild( container);
};
const handleEditPreset = (e) =>{
  console.log("a")
  const wrapper = e.target.parentElement.parentElement.parentElement
  handlePresetBut('edit',{arr:wrapper.attributes.data,name:wrapper.attributes.name,presetId:wrapper.attributes.presetId});
  console.log(wrapper.attributes.data)
}

function updateTemperature() {
    console.log("Fetching temperature...");

    fetch('../server/get_temp.php', {
        headers: {
            'Authorization': 'Bearer ' + localStorage.getItem('token')
        }
    })
    .then(response => response.json())
    .then(data => {
        const tempElement = document.getElementById('temp-value');
        if (!tempElement) return;

        if (data.success && data.temperature !== null) {
            const newTemp = data.temperature;
            const currentText = tempElement.textContent;
            const match = currentText.match(/[\d.]+/);
            const currentTemp = match ? parseFloat(match[0]) : newTemp;

            animateTemperature(currentTemp, newTemp, tempElement);
        } else {
            tempElement.innerHTML = `Unknown`;
        }
    })
    .catch(error => {
        console.error("Error:", error);
        const tempElement = document.getElementById('temp-value');
        if (tempElement) tempElement.innerHTML = `Unknown`;
    });
}

function animateTemperature(start, end, element) {
    const duration = 500; // in ms
    const steps = 30;
    const stepTime = duration / steps;
    let currentStep = 0;

    const delta = end - start;

    const interval = setInterval(() => {
        currentStep++;
        const progress = currentStep / steps;
        const value = start + delta * easeOutQuad(progress);
        element.innerHTML = `${value.toFixed(2)} °C`;

        if (currentStep >= steps) {
            clearInterval(interval);
            element.innerHTML = `${end.toFixed(2)} °C`;
        }
    }, stepTime);
}

// Optional easing for smoother look
function easeOutQuad(t) {
    return t * (2 - t);
}

async function selectPreset(preset_id) {
  console.log("ID" + preset_id)
  try {
    const jwtToken = localStorage.getItem('token');
    if (!jwtToken) throw new Error('No JWT token found in localStorage');
    if (!preset_id) throw new Error('No preset_id found on the event target');
    const response = await fetch('../server/select_preset.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + jwtToken
      },
      body: JSON.stringify({ preset_id: Number(preset_id) })
    });
    const data = await response.json();
    console.log(data)
    if (response.ok) {
      const presetNameElem = document.getElementById('selected-preset-name');
      if (presetNameElem && data.name) presetNameElem.innerHTML = data.name;
      return data;
    } else throw new Error(data.error || data.message || 'Failed to select preset');
  } catch (error) {
    throwError(error);
    return null;
  }
}

async function updateSelectedPresetName() {
  try {
    const jwtToken = localStorage.getItem('token');
    if (!jwtToken) throw new Error('No JWT token found in localStorage');

    const response = await fetch('../server/get_selected_preset.php', {
      headers: {
        'Authorization': 'Bearer ' + jwtToken
      }
    });

    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(errorData.error || 'Failed to get selected preset');
    }

    const data = await response.json();
    const presetName = data.name || 'None';
    const el = document.getElementById('selected-preset-name');
    if (el) el.innerHTML = presetName;
  } catch (error) {
    console.error('Error updating selected preset name:', error);
  }
}

async function checkActiveStatus() {
  try {
    const token = localStorage.getItem('token');
    if (!token) throw new Error('No token found');

    const response = await fetch('../server/check_active.php', {
      method: 'GET',
      headers: {
        'Authorization': 'Bearer ' + token
      }
    });
    console.log (response)
    if (response.status === 200) {
      // All good, do nothing
      return;
    }

    const data = await response.json();
    if (data.error === 'unavailable') {
      const unavailableDiv = document.getElementById('unavailable');
      if (unavailableDiv) {
        unavailableDiv.style.display = 'flex';
        setTimeout(() => {
          unavailableDiv.classList.add('visible');
        }, 50);
      }
    }
  } catch (error) {
    throwError("Something went wrong")
  }
}
checkActiveStatus()
setInterval(() => {
  document.getElementById("unavailable").style.display==="none" ? null :  checkActiveStatus()
}, 1000 * 60);

const usePreset = async () =>{
  const res = await fetch('../server/use_preset.php',{headers:{Authorization: `Bearer ${await localStorage.getItem('token')}`}})
  await res.json() ; console.log(res); 
  if(!res.ok)
    throwError(res.message)
}
const useAuto = async () =>{
  const res = await fetch('../server/use_auto.php',{headers:{Authorization: `Bearer ${await localStorage.getItem('token')}`}})
  await res.json()
  if(!res.ok)
    throwError(res.message)
}
const reloadPage = () =>{
  location.reload();
}



function sendCommand(cmd) {
    fetch('http://192.168.100.128/opticlean/server/control.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'command=' + encodeURIComponent(cmd)
    })
    .then(response => response.text())
    .then(result => {
        console.log(result);
        alert("Trimis: " + cmd + "\nRăspuns:\n" + result);
    })
    .catch(error => {
        console.error('Eroare:', error);
        alert("Eroare: " + error);
    });
}


function sendCommand2(cmd) {
    fetch('http://192.168.100.128/opticlean/server/control3.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'command=' + encodeURIComponent(cmd)
    })
    .then(response => response.text())
    .then(result => {
        console.log(result);
        alert("Trimis: " + cmd + "\nRăspuns:\n" + result);
    })
    .catch(error => {
        console.error('Eroare:', error);
        alert("Eroare: " + error);
    });
}

function sendCommand3(cmd) {
    fetch('http://192.168.100.128/opticlean/server/control_spray.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'command=' + encodeURIComponent(cmd)
    })
    .then(response => response.text())
    .then(result => {
        console.log(result);
        alert("Trimis: " + cmd + "\nRăspuns:\n" + result);
    })
    .catch(error => {
        console.error('Eroare:', error);
        alert("Eroare: " + error);
    });
}
