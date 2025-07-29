const switchContent = (OLD,NEW) =>{
    document.querySelectorAll('.login-input').forEach((el)=>{el.value=''})
    OLD = document.querySelector(`.${OLD}`)
    NEW = document.querySelector(`.${NEW}`)
    OLD.classList.add("hidden");
    setTimeout(() => {
        OLD.style.display="none";
        NEW.style.display="flex";
        setTimeout(() => {
            NEW.classList.remove("hidden")
        }, 10);
    }, 300);
}

const login = () =>{
    const username  = document.querySelector(".login-input-username.login").value;
    const password = document.querySelector(".login-input-password.login").value;
    fetch('../server/login.php',{method:'POST',
        headers:{'Content-Type': 'application/json'},
        body:JSON.stringify({username,password})
    })
    .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json(); 
    })
    .then(data => {
        console.log(data); 
        if (data.token) {
            localStorage.setItem('token', data.token);
        } else {
            console.error('Token not received');
        }
    })
    .then(()=>{window.location = '/opticlean/client/index.html'})
    .catch(error => {
        console.error('Fetch error:', error);
    });

}

const signup = () =>{
    const username  = document.querySelector(".login-input-username.signup").value;
    console.log(username)
    const password = document.querySelector(".login-input-password.signup").value;
    const repeatPassword = document.querySelector(".login-input-repeat-password.signup").value;
    fetch('../server/signup.php',{method:'POST',
        headers:{'Content-Type': 'application/json'},
        body:JSON.stringify({username,password,repeat_password:repeatPassword})
    })
    .then(response => {
        if (!response.ok) throw new Error('Signup failed');
        return response.json();
    })
    .then(data => {
        console.log(data); 
        if (data.token) {
        localStorage.setItem('token', data.token);
        } else {
        console.error('Token not received from signup');
        }
    })
    .then(()=>{window.location = '/opticlean/client/index.html'})
    .catch(error => {
        console.error('Signup error:', error);
    });
}

window.addEventListener('keypress',(e)=>{
    console.log(e.key)
    islogin = !document.querySelector('.log-in-div').classList.contains("hidden")
    issignup = !document.querySelector('.sign-up-div').classList.contains("hidden")
    if(islogin && e.key==="Enter") login()
    if(issignup && e.key==="Enter") signup()
})

window.onload =  ()=>{
    setInterval(() => {
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
        console.log('Parsed JSON:', data);
        if(data.valid){window.location = '/opticlean/client/index.html'}
        else localStorage.removeItem('token')
    } catch(e) {
        console.error('Invalid JSON:', e);
    }
    })
    .catch(err => {
    console.error('Fetch error:', err);
    });
    }, 1000);
}