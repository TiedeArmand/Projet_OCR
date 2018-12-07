#include "fonction.h"
#include <QTextStream>
double Teinte = 0;double Luminance = 0;double Saturation = 0;double Rouge = 0;double Vert = 0;double Bleu = 0;
Mat image,imageC1,imageC2,imageC3,imageInt, imageExt,imageZone;
int anneeMin = 2009; //l'interval de temps à considérer sachant que l'anneeMax = anneeMin + 10
////////////////////
Mat src, erosion_dst, dilation_dst, open_dst, close_dst;
int erosion_elem = 1;
int erosion_size = 1;
int dilation_elem = 1;
int dilation_size = 1;
///////////////////
string fichierstd;
bool traitement = false;
int nbCourbe = 0;
//vector<vector<string> > TabDesignation;


Fenetre::Fenetre() : QWidget()

{
    setFixedSize(1000, 700);

    /////////////////////////////////////////////////////////////////////////
    //bouton1
    bouton1 = new QPushButton("Vue",this);
    bouton1->setGeometry(735, 650, 70, 30);
    bouton1->setToolTip("Texte d'aide");
    bouton1->setFont(QFont("Comic Sans MS", 10, QFont::Bold, true));
    bouton1->setCursor(Qt::PointingHandCursor);
    //bouton2
    bouton2 = new QPushButton("Valider", this);
    bouton2->setGeometry(820,650, 70, 30);
    bouton2->setToolTip("Texte d'aide");
    bouton2->setFont(QFont("Comic Sans MS", 10, QFont::Bold, true));
    bouton2->setCursor(Qt::PointingHandCursor);
    //bouton3
    bouton3 = new QPushButton("Quitter", this);
    bouton3->setGeometry(905, 650, 70, 30);
    bouton3->setToolTip("Texte d'aide");
    bouton3->setFont(QFont("Comic Sans MS", 10, QFont::Bold, true));
    bouton3->setCursor(Qt::PointingHandCursor);
    //bouton4
    bouton4 = new QPushButton("Image", this);
    bouton4->setGeometry(670, 225, 70, 30);
    bouton4->setToolTip("Texte d'aide");
    bouton4->setFont(QFont("Comic Sans MS", 8, QFont::Bold, true));
    bouton4->setCursor(Qt::PointingHandCursor);
    //bouton5
    bouton5 = new QPushButton("Sélection Pixel", this);
    bouton5->setGeometry(750, 225, 110, 30);
    bouton5->setToolTip("Texte d'aide");
    bouton5->setFont(QFont("Comic Sans MS", 8, QFont::Bold, true));
    bouton5->setCursor(Qt::PointingHandCursor);
    //bouton6
    bouton6 = new QPushButton("Sélection Couleur", this);
    bouton6->setGeometry(870, 225, 125, 30);
    bouton6->setToolTip("Texte d'aide");
    bouton6->setFont(QFont("Comic Sans MS", 8, QFont::Bold, true));
    bouton6->setCursor(Qt::PointingHandCursor);
    //bouton7
    bouton7 = new QPushButton("OCR", this);
    bouton7->setGeometry(700, 260, 110, 30);
    bouton7->setToolTip("Texte d'aide");
    bouton7->setFont(QFont("Comic Sans MS", 8, QFont::Bold, true));
    bouton7->setCursor(Qt::PointingHandCursor);

    lcd = new QLCDNumber(this);
    lcd->setSegmentStyle(QLCDNumber::Flat);
    lcd->move(765, 190);
    slider = new QSlider(Qt::Horizontal, this);
    slider->setRange(0, 255);
    slider->setGeometry(720, 170, 150, 20);
    texte = new QLabel("Seuil",this);
    texte->setFont(QFont("Comic Sans MS", 13, QFont::Bold, true));
    texte->move(780, 150);
    cocheseuil = new QCheckBox(this);
    cocheseuil->move(760, 150);

    //Rouge
    texte1 = new QLabel("Rouge",this);
    texte1->setFont(QFont("Comic Sans MS", 12, QFont::Bold, true));
    texte1->move(680, 30);
    lcd1 = new QLCDNumber(this);
    lcd1->setSegmentStyle(QLCDNumber::Flat);
    lcd1->move(675, 50);

    //Vert
    texte2 = new QLabel("Vert",this);
    texte2->setFont(QFont("Comic Sans MS", 12, QFont::Bold, true));
    texte2->move(780, 30);
    lcd2 = new QLCDNumber(this);
    lcd2->setSegmentStyle(QLCDNumber::Flat);
    lcd2->move(770, 50);

    //Bleu
    texte3 = new QLabel("Bleu",this);
    texte3->setFont(QFont("Comic Sans MS", 12, QFont::Bold, true));
    texte3->move(870, 30);
    lcd3 = new QLCDNumber(this);
    lcd3->setSegmentStyle(QLCDNumber::Flat);
    lcd3->move(860, 50);

    //Teinte
    texte4 = new QLabel("Teinte",this);
    texte4->setFont(QFont("Comic Sans MS", 12, QFont::Bold, true));
    texte4->move(680, 90);
    lcd4 = new QLCDNumber(this);
    lcd4->setSegmentStyle(QLCDNumber::Flat);
    lcd4->move(675, 110);

    //Saturation
    texte5 = new QLabel("Saturation",this);
    texte5->setFont(QFont("Comic Sans MS", 12, QFont::Bold, true));
    texte5->move(755, 90);
    lcd5 = new QLCDNumber(this);
    lcd5->setSegmentStyle(QLCDNumber::Flat);
    lcd5->move(770, 110);

    //Luminance
    texte6 = new QLabel("Luninance",this);
    texte6->setFont(QFont("Comic Sans MS", 12, QFont::Bold, true));
    texte6->move(860, 90);

    lcd6 = new QLCDNumber(this);
    lcd6->setSegmentStyle(QLCDNumber::Flat);
    lcd6->move(860, 110);

    onglets = new QTabWidget(this);
    onglets->setGeometry(30, 20, 630, 630);
    page1 = new QLabel;//onglet 1
    page2 = new QLabel;//onglet 2
    page3 = new QLabel;//onglet 3
      //  page4->setPixmap(QPixmap("icone.png"));
      //  page4->setAlignment(Qt::AlignCenter);
    page5 = new QLabel(this);//pour afficher l'image HSV
          page5 ->setGeometry(675,300,10,10);
          page5->setPixmap(QPixmap("/home/djiro/annotations/color3d.jpg"));
          page5->setScaledContents(true);
          page5->adjustSize();
    onglets->addTab(page1, "Image Originale");
    onglets->addTab(page2, "Transformation gray");
    onglets->addTab(page3, "Segmentation");
    /////////////////////////////////////////////////////////////////////////

    QObject::connect(bouton3, SIGNAL(clicked()), qApp, SLOT(quit()));
    QObject::connect(bouton4, SIGNAL(clicked()), this, SLOT(ouvrirDialogueChoisir()));
    QObject::connect(bouton5, SIGNAL(clicked()), this, SLOT(selectionPixel()));
    QObject::connect(bouton6, SIGNAL(clicked()), this, SLOT(selectionMarqueur()));
    QObject::connect(bouton7, SIGNAL(clicked()), this, SLOT(Traitement_OCR()));
    QObject::connect(slider, SIGNAL(valueChanged(int)), lcd,SLOT(display(int)));
}
//////////////////////////////////////////
//////////////////////////////////////////

void Fenetre::Traitement_OCR()
{
    if(!nomFichier.isNull() && traitement == true)
    {
    image = imread(fichierstd);
    cvtColor( image, imageC1, CV_BGR2GRAY );

    imwrite( "gray.png", imageC1 );

    //niBlackThreshold(imageC1, imageC2, 255, CV_THRESH_BINARY, 5, 8, CV_BINARIZATION_NIBLACK);


    //string commande = "C:\Program Files (x86)\Tesseract-OCR>tesseract Otsu.png out -psm 3";
	
    string commande = "tesseract Otsu.png out -l fra -psm 6 tsv";
    system(commande.c_str());


    string ligne,mot;
    ////////////////////////////
    ///
    string date,taux, tva,totalHT,totalTTC ; int b;
    //////////////////////////////////////////////////////////////
        ifstream fichier("out.tsv");

        /////////////////////////////////////////////////////////
        //////
        //ifstream fichier("C:\Users\djiro\AppData\Local\VirtualStore\Program Files (x86)\Tesseract-OCR\out.txt");
        ofstream fichierExel("fichExcel.xlsx");
        ofstream fichierExel2("fichExcel2.xlsx");
        fichierExel << "Date\t"; fichierExel << "Designation\t"; fichierExel << "Quantité\t"; fichierExel << "taux\t"; fichierExel << "TVA\t"; fichierExel << "HT\t";  fichierExel << "TTC\t"; fichierExel << "\n";
        /////////////////////////////////////////////////////////

        vector<vector<string> > TabDesignation;
       // vector<vector<string> > TabDesignation2;

        while(getline(fichier, ligne))
        {

            TabDesignation.push_back(vector<string>(12));// 12 est le Nb cases d'un fichier tsv
            ofstream fichier1("fichier1.txt");
            fichier1 << ligne;
            fichier1.close();
            ifstream fichier2("fichier1.txt");
            while(!fichier2.eof())
            {
                for(int i=0; i<12; i++)
                {
                    fichier2 >> TabDesignation[TabDesignation.size()-1][i];
                    cout << TabDesignation[TabDesignation.size()-1][i] << "\t";
                }
            }
            cout << "\t" << TabDesignation.size() << endl;



//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            mot = ligne[0];

            if ((atoi(mot.c_str())<10) && (atoi(mot.c_str())>0))
            {

                //supprimer les ecpaces de la ligne et mettre espace entre chiffre et lettre
                for (int i = 0; i<ligne.size(); i++)
                {
                    if ((ligne[i] == ' ') && ((ligne[i-1] == '.')||(ligne[i+1] == '.')))
                    {
                        ligne.erase(i, 1);
                    }
                }
 ///////////////////////////////////////////////////////
                ofstream fichier1("fichier1.txt");
                fichier1 << ligne;
                fichier1.close();

                ifstream fichier2("fichier1.txt");
/////////////////////////////////////////////////////////

            }

            else
            {

            while(fichier2 >> mot)
              {
                //comparer les mots pour reconnaitre les mots
                b = atoi(mot.c_str());
                int anneeMax = anneeMin + 10;
                for (int i = anneeMin; i <= anneeMax; i++)
                {
                    if (b == i)
                    {
                        date = ligne;
                      //  fichierExel << "Date\t"; fichierExel << ligne; fichierExel << "\n";
                    }
                }

                if ( mot == "HT" || mot == "ht" )
                {
                    fichier2 >> mot;
                    totalHT = mot;
                   // fichierExel << "HT\t"; fichierExel << mot; fichierExel << "\n";
                }

                //if ( mot == "Total" || mot == "TTC" )
                if ( mot == "Total" || mot == "TTC" || mot == "pvr" || mot == "PVR" )
                {
                    fichier2 >> mot;
                    fichier2 >> mot;
                    totalTTC = mot;
                   // fichierExel << "TTC\t"; fichierExel << mot; fichierExel << "\n";
                }

                if ( mot == "tva" || mot == "TVA" )
                {
                    fichier2 >> mot;
                    taux = mot;
                  //  fichierExel << "TVA\t"; fichierExel << mot; fichierExel << "\t";

                    fichier2 >> mot;
                    tva = mot;
                 //   fichierExel << mot;
                 //   fichierExel << "\n";
                }


              }
            }


        }
        /////////////////////////////////////////////
        for (int i = 0; i<TabDesignation.size(); i++)
        {
            fichierExel << date; fichierExel << "\t"; fichierExel << TabDesignation[i][1]; fichierExel << "\t"; fichierExel << TabDesignation[i][0]; fichierExel << "\t\t\t\t"; fichierExel << TabDesignation[i][2]; fichierExel << "\n";

        }

        fichierExel << "\t\t"; fichierExel << "\t"; fichierExel << taux; fichierExel << "\t"; fichierExel << tva; fichierExel << "\t"; fichierExel << totalHT; fichierExel << "\t"; fichierExel << totalTTC; fichierExel << "\n";

        fichier.close();


        ////////////////////////////////////
        ////////////////////////////////////

        ifstream fich("out2.txt");
        vector<vector<string> > tableau;
        tableau.push_back(vector<string>(4));
        tableau[0][0]= "Libelle";
        tableau[0][1]= "Date";
        tableau[0][2]= "Debit";
        tableau[0][3]= "Credit";

        while(getline(fich, ligne))
        {

            ofstream fichier1("fichier1.txt");
            fichier1 << ligne;
            fichier1.close();

            ifstream fichier2("fichier1.txt");

            fichier2 >> mot;
            //while(fichier2 >> mot)
            //{
                if ((mot == "DATE") || (mot == "date"))
                {
                    while(getline(fich, ligne))
                    {
                   // getline(fich, ligne);
                    //getline(fich, ligne);

                    ofstream fichier1("fichier2.txt");
                    fichier1 << ligne;
                    fichier1.close();

                    ifstream fichier11("fichier2.txt");
                    fichier11 >> mot;

                    //Au Debit
                    if(mot == "VIREMENT" || mot == "PRET" || mot == "CHQ." || mot == "PRLV")
                    {


                        //supprimer les ecpaces de la ligne et mettre espace entre chiffre et lettre

                        for (int i = 0; i<ligne.size(); i++)
                        {
                            if (((ligne[i] == ' ') && ((ligne[i-1] == '.')||(ligne[i+1] == '.'))) || ((ligne[i] == ' ') && (ligne[i+1] != '0') && (ligne[i+1] != '1') && (ligne[i+1] != '2') && (ligne[i+1] != '3')&& (ligne[i+1] != '4') && (ligne[i+1] != '5') && (ligne[i+1] != '6') && (ligne[i+1] != '7') && (ligne[i+1] != '8') && (ligne[i+1] != '9')))
                            {
                                ligne.erase(i, 1);
                            }
                        }
         ///////////////////////////////////////////////////////
                        ofstream fichier1("fichier1.txt");
                        fichier1 << ligne;
                        fichier1.close();

                        ifstream fichier4("fichier1.txt");
                        tableau.push_back(vector<string>(4));
                        tableau[tableau.size()-1][0]= "NULL";
                        tableau[tableau.size()-1][1]= "NULL";
                        tableau[tableau.size()-1][2]= "NULL";
                        tableau[tableau.size()-1][3]= "NULL";
                        int i = 0;
                        while(!fichier4.eof())
                        {
                            //tableau[0][0]= "MAMAN";
                            fichier4 >> mot;
                            tableau[tableau.size()-1][i] = mot;
                            i+=1;
                        }
                        fichier4.close();
                    }

                    else if(mot == "ANCIEN" || mot == "VIR")
                    {

                        //supprimer les ecpaces de la ligne et mettre espace entre chiffre et lettre
                        for (int i = 0; i<ligne.size(); i++)
                        {
                            if (((ligne[i] == ' ') && ((ligne[i-1] == '.')||(ligne[i+1] == '.'))) || ((ligne[i] == ' ') && (ligne[i+1] != '0') && (ligne[i+1] != '1') && (ligne[i+1] != '2') && (ligne[i+1] != '3')&& (ligne[i+1] != '4') && (ligne[i+1] != '5') && (ligne[i+1] != '6') && (ligne[i+1] != '7') && (ligne[i+1] != '8') && (ligne[i+1] != '9')))
                            {
                                ligne.erase(i, 1);
                            }
                        }
                        ofstream fichier1("fichier1.txt");
                        fichier1 << ligne;
                        fichier1.close();

                        ifstream fichier4("fichier1.txt");
                        tableau.push_back(vector<string>(4));
                        tableau[tableau.size()-1][0]= "NULL";
                        tableau[tableau.size()-1][1]= "NULL";
                        tableau[tableau.size()-1][2]= "NULL";
                        tableau[tableau.size()-1][3]= "NULL";
                        int i = 0;
                        while(!fichier4.eof())
                        {
                            fichier4 >> mot;
                            tableau[tableau.size()-1][i]= mot;
                            i+=1;
                            if(i==2)
                            {
                                i+=1;
                            }
                        }
                        fichier4.close();
                    }

                    else
                    {
                        //supprimer les ecpaces de la ligne et mettre espace entre chiffre et lettre
                        for (int i = 0; i<ligne.size(); i++)
                        {
                            if (((ligne[i] == ' ') && ((ligne[i-1] == '.')||(ligne[i+1] == '.'))) || ((ligne[i] == ' ') && (ligne[i+1] != '0') && (ligne[i+1] != '1') && (ligne[i+1] != '2') && (ligne[i+1] != '3')&& (ligne[i+1] != '4') && (ligne[i+1] != '5') && (ligne[i+1] != '6') && (ligne[i+1] != '7') && (ligne[i+1] != '8') && (ligne[i+1] != '9')))
                            {
                                ligne.erase(i, 1);
                            }
                        }
                        tableau[tableau.size()-1][0]= tableau[tableau.size()-1][0] + ligne;
                    }

                    fichier11.close();

                }
                }

            //}

        }

        for (int i = 0; i<tableau.size(); i++)
        {
            fichierExel2 << tableau[i][0]; fichierExel2 << "\t"; fichierExel2 << tableau[i][1]; fichierExel2 << "\t"; fichierExel2 << tableau[i][2]; fichierExel2 << "\t"; fichierExel2 << tableau[i][3]; fichierExel2 << "\n";

        }


        ////////////////////////////////////
        ////////////////////////////////////

        nomFichier = "";

    }
    else
    {
      QMessageBox::critical(this, "Information"," Pas d'image ou de couleur de traitement sélectionnée ");
    }
}





void Fenetre::ouvrirDialogueChoisir()
{
   nomFichier = "";
   nomFichier = QFileDialog::getOpenFileName(this, "Ouvrir un fichier",QString(), "Images (*.tif *.bmp *.png *.gif *.jpg *.jpeg)");

   if(!nomFichier.isNull())
   { 
       fichierstd = nomFichier.toStdString();
       IplImage *imag = cvLoadImage(fichierstd.c_str());
       cvSaveImage("imageOrig.png",imag);

    // page1->setPixmap(QPixmap(nomFichier));//n'affiche pas les fichiers .tif
       page1->setPixmap(QPixmap("imageOrig.png"));
       page1->setScaledContents(true);
       page1->adjustSize();

       page2->setPixmap(QPixmap("/home/djiro/annotations/ima.png"));
       page2->setScaledContents(true);
       page2->adjustSize();

       page3->setPixmap(QPixmap("/home/djiro/annotations/ima.png"));
       page3->setScaledContents(true);
       page3->adjustSize();

       traitement = false;
   }

   else
   {
     QMessageBox::critical(this, "Information"," Pas d'image sélectionnée ");
   }
}



////////////////////////////////////////////////////////////////////////////////////

void Fenetre::selectionPixel()//cette fonction permet la selection d'une couleur puis une transformation 1,2,ou 3 est effectuée
{
    if(!nomFichier.isNull())
    {
        vector<double> TabCourbe;
        string fichierstd = nomFichier.toStdString();
        Mat img = imread(fichierstd);
        namedWindow("Choix Pixel", 1);
        setMouseCallback("Choix Pixel", mouseEvent, &img);
        imshow("Choix Pixel", img);
        waitKey(0);
        //traitement image puis affichage dans page2
        lcd1->display(Rouge);
        lcd2->display(Vert);
        lcd3->display(Bleu);
        lcd4->display(Teinte);
        lcd5->display(Saturation);
        lcd6->display(Luminance);
        destroyWindow("Choix Pixel");
int choix =2;

        //////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////
    /*  if (choix==1)
      {
          fonctionNicolas();
      }*/
       if (choix==2)
      {
          fonctionNicolasArmand();
      }
      if (choix==3)
      {
          fonctionCarron();
      }
          imwrite( "imageC1.bmp", imageC1 );
          page2->setPixmap(QPixmap("/home/djiro/build-Qt_teste-Sans_nom-Debug/imageC1.bmp"));
          page2->setScaledContents(true);
          page2->adjustSize();
          //////////////////////////////////////////////
          ////////////////////////////////////////////
          // Create a structuring element
                int erosion_size = 1;
                Mat element = getStructuringElement(cv::MORPH_CROSS,
                       cv::Size(2 * erosion_size + 1, 2 * erosion_size + 1),
                       cv::Point(-1, -1) );

          //detection contoures
              //noyaux

                medianBlur(imageC1,imageC2,5);
                Canny(imageC2,imageC2,10,350);
                bitwise_not ( imageC2, imageC2 );
                threshold(imageC1, imageC3, 0, 255, CV_THRESH_BINARY | CV_THRESH_OTSU);
  /*
              //contours
                medianBlur(imageC1,imageC2,3);
                //dilate(imageC2,imageC2,element);
                Canny(imageC2,imageC2,10,350);
                //erode (imageC2,imageC2,element); */

          //imshow( "canny", imageC2);
         imwrite( "canny.bmp", imageC2 );
         imwrite("Otsu.bmp",imageC3);
         page3->setPixmap(QPixmap("/home/djiro/build-Qt_teste-Sans_nom-Debug/Otsu.bmp"));
         page3->setScaledContents(true);
         page3->adjustSize();
    }
    else
    {
      QMessageBox::information( this, "Information"," Pas d'image sélectionnée ");
    }
}

void Fenetre::selectionMarqueur()//cette fonction permet le selection d'un marqueur puis fait une transformation couleur en fonction
{
    if(!nomFichier.isNull())
    {

        //vector<double> TabCourbe;
        marqueur = "";
        bool ok;
        QStringList items;
        items << "Rouge" << "Jaune" << "Vert" << "Cyan" << "Bleu" << "Magenta" << "Noir";
        marqueur = QInputDialog::getItem(this, "Seletion de Marqueur","Marqueurs:", items, 0, false, &ok);

        string itemstd = marqueur.toStdString();
        if (ok && !marqueur.isEmpty())
     //   string nomteinte = itemstd;
        string fichierstd = nomFichier.toStdString();

        //Teinte Rouge
        if (itemstd == "Rouge")
        {
            Teinte = 0;
            lcd1->display(1);
            lcd2->display(0);
            lcd3->display(0);
            lcd4->display(0);
            lcd5->display(255);
            lcd6->display(0);
            fonctionNicolasArmand();
        }

        //Teinte Jaune
        if (itemstd == "Jaune")
        {
            Teinte = 60;
            lcd1->display(1);
            lcd2->display(1);
            lcd3->display(0);
            lcd4->display(60);
            lcd5->display(255);
            lcd6->display(0);
            fonctionNicolasArmand();
        }

        //Teinte Verte
        else if (itemstd == "Vert")
        {
            Teinte = 120;
            lcd1->display(0);
            lcd2->display(1);
            lcd3->display(0);
            lcd4->display(120);
            lcd5->display(255);
            lcd6->display(0);
            fonctionNicolasArmand();
        }

        //Teinte Cyan
        else if (itemstd == "Cyan")
        {
            Teinte = 180;
            lcd1->display(0);
            lcd2->display(1);
            lcd3->display(1);
            lcd4->display(180);
            lcd5->display(255);
            lcd6->display(0);
            fonctionNicolasArmand();
        }

        //Teinte Bleu
        else if (itemstd == "Bleu")
        {
            Teinte = 240;
            lcd1->display(0);
            lcd2->display(0);
            lcd3->display(1);
            lcd4->display(240);
            lcd5->display(255);
            lcd6->display(0);
            fonctionNicolasArmand();
        }

        //Teinte Magenta
        else if (itemstd == "Magenta")
        {
            Teinte = 300;
            lcd1->display(1);
            lcd2->display(0);
            lcd3->display(1);
            lcd4->display(300);
            lcd5->display(255);
            lcd6->display(0);
            fonctionNicolasArmand();
        }

        //Teinte Noir
        else if (itemstd == "Noir")
        {
            Teinte = 0;
            lcd1->display(0);
            lcd2->display(0);
            lcd3->display(0);
            lcd4->display(0);
            lcd5->display(0);
            lcd6->display(0);
            fonctionNicolasArmand_Noir();
        }


        cout<< "Taille Image : "; cout<< imageC1.rows; cout << " "; cout << imageC1.cols <<endl;

        imwrite( "imageC1.bmp", imageC1 );
        page2->setPixmap(QPixmap("imageC1.bmp"));
        page2->setScaledContents(true);
        page2->adjustSize();
        /////////////////////////////////////////////////////////
        // Create a structuring element
        /*      int erosion_size = 1;
              Mat element = getStructuringElement(cv::MORPH_CROSS,
                     cv::Size(2 * erosion_size + 1, 2 * erosion_size + 1),
                     cv::Point(-1, -1) );

        //detection contoures
            //noyaux

              medianBlur(imageC1,imageC2,5);
              Canny(imageC2,imageC2,10,350);
              bitwise_not ( imageC2, imageC2 );*/

              threshold(imageC1, imageC3, 0, 255, CV_THRESH_BINARY | CV_THRESH_OTSU);
              /*
              distanceTransform(imageC3, imageC3, CV_DIST_L2, 3);
              normalize(imageC3, imageC3, 0, 1., NORM_MINMAX);
              imshow("Distance Transform Image", imageC3);*/
/*
            //contours
              medianBlur(imageC1,imageC2,3);
              //dilate(imageC2,imageC2,element);
              Canny(imageC2,imageC2,10,350);
              //erode (imageC2,imageC2,element); */

        //imshow( "canny", imageC2);
       //imwrite( "canny.bmp", imageC2 );
       imwrite( "Otsu.bmp", imageC3 );
       //page3->setPixmap(QPixmap("/home/djiro/build-Qt_teste-Sans_nom-Debug/Otsu.bmp"));
       page3->setPixmap(QPixmap("Otsu.bmp"));
       page3->setScaledContents(true);
       page3->adjustSize();

    }
    else
    {
      QMessageBox::information( this, "Information"," Pas d'image sélectionnée ");
    }
}
/////////////////////////////////////////////////////////////////////////

/////////////////////////////////////////
void Fenetre::mouseEvent(int evt, int x, int y, int flags, void* param)
{
    Mat* rgb = (Mat*) param;
    if (evt == CV_EVENT_LBUTTONDOWN)
    {
        printf("%d %d BGR: %d, %d, %d\n",
        x, y,
        (int)(*rgb).at<Vec3b>(y, x)[0],
        (int)(*rgb).at<Vec3b>(y, x)[1],
        (int)(*rgb).at<Vec3b>(y, x)[2]);

    Bleu =(int)(*rgb).at<Vec3b>(y, x)[0];
    Vert =(int)(*rgb).at<Vec3b>(y, x)[1];
    Rouge =(int)(*rgb).at<Vec3b>(y, x)[2];

          Mat HSV;
          Mat RGB=(*rgb)(Rect(x,y,1,1));
          cvtColor(RGB, HSV,CV_BGR2HLS);

            Vec3b hsv=HSV.at<Vec3b>(0,0);
    Teinte =2*hsv.val[0];
    Luminance =hsv.val[1];
    Saturation =hsv.val[2];

            printf("%d %d HLS: %d, %d, %d\n",
                   x, y,2*hsv.val[0],hsv.val[1],hsv.val[2]);
    }
}


void Fenetre::fonctionNicolasArmand()
{
    //traitement par teinte
        double maxC1,minC1,Teinte1,distance,e;

        image = imread(fichierstd);
        IplImage *image2 = cvLoadImage(fichierstd.c_str());
        IplImage* hls = cvCloneImage(image2);
        cvCvtColor(image2, hls, CV_BGR2HLS);
        cvtColor( image, imageC1, CV_BGR2GRAY );
        cvtColor( image, imageC2, CV_BGR2GRAY );
        cvtColor( image, imageC3, CV_BGR2GRAY );
        cvtColor( image, imageInt, CV_BGR2GRAY );
        cvtColor( image, imageExt, CV_BGR2GRAY );
        cvtColor( image, imageZone, CV_BGR2GRAY );
        double pi = 3.14159265358979323846;double delta = 0;
        int i,j;
        CvScalar pixel;
        //////////////////
        e=0.001;
        pixel=cvGet2D(hls,0,0);
        Teinte1 = 2* pixel.val[0];

        //calcule distance de teinte
        delta = abs(Teinte1-Teinte)*2*pi/360;
        distance = 255*(exp((cos(delta)-1)/(cos(delta)+1+e)));//3
        maxC1 = 255-((distance*pixel.val[2]*(255-pixel.val[1]))/(255*(pixel.val[2]+1)));minC1 =maxC1;//3

        for ( i=0; i<image.rows; i++)
          {
             for ( j=0; j<image.cols; j++)
             {
                 pixel=cvGet2D(hls,i,j);
                 Teinte1 = 2* pixel.val[0];

                 delta = abs(Teinte1-Teinte)*2*pi/360;
                 distance = 255*(exp((cos(delta)-1)/(cos(delta)+1+e)));//3
                 distance = 255-((distance*pixel.val[2]*(255-pixel.val[1]))/(255*(pixel.val[2]+1)));//3


                 if (distance>maxC1)
                 {
                     maxC1 = distance;
                 }
                 if (distance<minC1)
                 {
                     minC1 = distance;
                 }
             }
        }

        for ( i=0; i<image.rows; i++)
           {
             for ( j=0; j<image.cols; j++)
             {
                pixel=cvGet2D(hls,i,j);
                Teinte1 = 2*pixel.val[0];

                delta = abs(Teinte1-Teinte)*2*pi/360;
                distance = 255*(exp((cos(delta)-1)/(cos(delta)+1+e)));//3
                distance = 255-((distance*pixel.val[2]*(255-pixel.val[1]))/(255*(pixel.val[2]+1)));//3
                imageC1.at<uchar>(i,j) = 255*(distance-minC1)/(maxC1-minC1);

             }
        }
        traitement = true;
}

void Fenetre::fonctionNicolasArmand_Noir()
{
    //traitement par teinte
        double maxC1,minC1,Teinte1,distance,e;

        image = imread(fichierstd);
        IplImage *image2 = cvLoadImage(fichierstd.c_str());
        IplImage* hls = cvCloneImage(image2);
        cvCvtColor(image2, hls, CV_BGR2HLS);
        cvtColor( image, imageC1, CV_BGR2GRAY );
        cvtColor( image, imageC2, CV_BGR2GRAY );
        cvtColor( image, imageC3, CV_BGR2GRAY );
        cvtColor( image, imageInt, CV_BGR2GRAY );
        cvtColor( image, imageExt, CV_BGR2GRAY );
        cvtColor( image, imageZone, CV_BGR2GRAY );
        double pi = 3.14159265358979323846;double delta = 0;
        int i,j;
        CvScalar pixel;
        //////////////////
        e=0.001;
        pixel=cvGet2D(hls,0,0);
        Teinte1 = 2* pixel.val[0];

        //calcule distance de teinte
        delta = abs(Teinte1-Teinte)*2*pi/360;
        distance = 255*(exp((cos(delta)-1)/(cos(delta)+1+e)));//3
        maxC1 = 255-((distance*(255-pixel.val[2])*(255-pixel.val[1]))/(255*(255-pixel.val[2]+1)));minC1 =maxC1;//3

        for ( i=0; i<image.rows; i++)
          {
             for ( j=0; j<image.cols; j++)
             {
                 pixel=cvGet2D(hls,i,j);
                 Teinte1 = 2* pixel.val[0];

                 delta = abs(Teinte1-Teinte)*2*pi/360;
                 distance = 255*(exp((cos(delta)-1)/(cos(delta)+1+e)));//3
                 distance = 255-((distance*(255-pixel.val[2])*(255-pixel.val[1]))/(255*(255-pixel.val[2]+1)));//3


                 if (distance>maxC1)
                 {
                     maxC1 = distance;
                 }
                 if (distance<minC1)
                 {
                     minC1 = distance;
                 }
             }
        }

        for ( i=0; i<image.rows; i++)
           {
             for ( j=0; j<image.cols; j++)
             {
                pixel=cvGet2D(hls,i,j);
                Teinte1 = 2*pixel.val[0];

                delta = abs(Teinte1-Teinte)*2*pi/360;
                distance = 255*(exp((cos(delta)-1)/(cos(delta)+1+e)));//3
                distance = 255-((distance*(255-pixel.val[2])*(255-pixel.val[1]))/(255*(255-pixel.val[2]+1)));//3
                imageC1.at<uchar>(i,j) = 255*(distance-minC1)/(maxC1-minC1);

             }
        }
        traitement = true;
}

/////////////////////////////////

void Fenetre:: Erosion( int, void* )
{
  int erosion_type = 0;
  if( erosion_elem == 0 ){ erosion_type = MORPH_RECT; }
  else if( erosion_elem == 1 ){ erosion_type = MORPH_CROSS; }
  else if( erosion_elem == 2) { erosion_type = MORPH_ELLIPSE; }
  Mat element = getStructuringElement( erosion_type,
                       Size( 2*erosion_size + 1, 2*erosion_size+1 ),
                       Point( erosion_size, erosion_size ) );
  erode( imageC3, erosion_dst, element );
  //imshow( "Erosion Demo", erosion_dst );
}
void Fenetre:: Dilation( int, void* )
{
  int dilation_type = 0;
  if( dilation_elem == 0 ){ dilation_type = MORPH_RECT; }
  else if( dilation_elem == 1 ){ dilation_type = MORPH_CROSS; }
  else if( dilation_elem == 2) { dilation_type = MORPH_ELLIPSE; }
  Mat element = getStructuringElement( dilation_type,
                       Size( 2*dilation_size + 1, 2*dilation_size+1 ),
                       Point( dilation_size, dilation_size ) );
  dilate( imageC3, dilation_dst, element );
  //imshow( "Dilation Demo", dilation_dst );
}
void Fenetre:: Ouverture( int, void* )
{
    int erosion_type = 0;
    if( erosion_elem == 0 ){ erosion_type = MORPH_RECT; }
    else if( erosion_elem == 1 ){ erosion_type = MORPH_CROSS; }
    else if( erosion_elem == 2) { erosion_type = MORPH_ELLIPSE; }
    //   int erosion_type = MORPH_OPEN;
    Mat element = getStructuringElement( erosion_type,
                         Size( 2*erosion_size + 1, 2*erosion_size+1 ),
                         Point( erosion_size, erosion_size ) );
  erode( imageC3, erosion_dst, element );
  dilate( erosion_dst, open_dst, element );
  //morphologyEx(imageC3, open_dst, element);
  //imshow( "Open Demo", open_dst );
}
void Fenetre:: Fermeture( int, void* )
{
  int dilation_type = 0;
  if( dilation_elem == 0 ){ dilation_type = MORPH_RECT; }
  else if( dilation_elem == 1 ){ dilation_type = MORPH_CROSS; }
  else if( dilation_elem == 2) { dilation_type = MORPH_ELLIPSE; }
  //  int dilation_type = MORPH_CLOSE;
  Mat element = getStructuringElement( dilation_type,
                       Size( 2*dilation_size + 1, 2*dilation_size+1 ),
                       Point( dilation_size, dilation_size ) );
  dilate( imageC3, dilation_dst, element );
  erode( dilation_dst, close_dst, element );
  //morphologyEx(imageC3, close_dst, element);
  //imshow( "Dilation Demo", close_dst );
}
