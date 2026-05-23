<div class="lorins-logo">
    <div class="logo-outer-circle">
        <div class="logo-oval">
            <div class="logo-text">
                <span class="logo-main">LORINS</span>
                <span class="logo-tm">®</span>
            </div>
            <div class="logo-since">Since 1973</div>
        </div>
    </div>
</div>

<style>
.lorins-logo {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
}

.logo-oval {
    width: 280px;
    height: 140px;
    background: #FF6B35;
    border: 3px solid white;
    border-radius: 50%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    box-shadow: 0 0 0 3px #FF6B35, 0 4px 15px rgba(255, 107, 53, 0.3);
    position: relative;
    outline: 3px solid #FF6B35;
    outline-offset: 0px;
}

.logo-text {
    display: flex;
    align-items: baseline;
    margin-bottom: 5px;
}

.logo-main {
    font-family: 'Georgia', 'Times New Roman', serif;
    font-size: 42px;
    font-weight: bold;
    color: white;
    letter-spacing: 2px;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
}

.logo-tm {
    font-size: 14px;
    color: white;
    margin-left: 3px;
    vertical-align: super;
    line-height: 0;
}

.logo-since {
    font-family: Arial, sans-serif;
    font-size: 14px;
    color: white;
    letter-spacing: 1px;
    margin-top: 5px;
}

/* Compact version for sidebar */
.sidebar .lorins-logo {
    padding: 15px 10px;
}

.sidebar .logo-oval {
    width: 200px;
    height: 100px;
}

.sidebar .logo-main {
    font-size: 32px;
}

.sidebar .logo-since {
    font-size: 11px;
}

.sidebar.collapsed .lorins-logo {
    padding: 10px 5px;
}

.sidebar.collapsed .logo-oval {
    width: 60px;
    height: 60px;
}

.sidebar.collapsed .logo-main {
    font-size: 18px;
    letter-spacing: 0;
}

.sidebar.collapsed .logo-since,
.sidebar.collapsed .logo-tm {
    display: none;
}

/* Header logo (smaller) */
.header .lorins-logo {
    padding: 0;
}

.header .logo-oval {
    width: 120px;
    height: 60px;
}

.header .logo-main {
    font-size: 24px;
}

.header .logo-since {
    font-size: 10px;
}

.header .logo-tm {
    font-size: 10px;
}

@media (max-width: 768px) {
    .logo-oval {
        width: 220px;
        height: 110px;
    }
    
    .logo-main {
        font-size: 36px;
    }
}
</style>
