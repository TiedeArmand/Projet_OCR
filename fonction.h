#ifndef FONCTION
#define FONCTION

#include <QtWidgets>
#include <opencv2/opencv.hpp>
#include <iostream>
#include <fstream>
#include <vector>
#include <cmath>
using namespace cv;
using namespace std;


class Fenetre : public QWidget
{
    Q_OBJECT


public:
    Fenetre();
    static void mouseEvent(int evt, int x, int y, int flags, void* param);
    static void fonctionNicolasArmand();
    static void fonctionNicolasArmand_Noir();
    static void Erosion( int, void* );
    static void Dilation( int, void* );
    static void Ouverture( int, void* );
    static void Fermeture( int, void* );

    static void fonctionCarron();
    static void rotationM90(Mat& image);
    static void rotation90(Mat& image);
    static void rotation180(Mat& image);

public slots:
    void ouvrirDialogueChoisir();
    void selectionPixel();
    void selectionMarqueur();
    void Traitement_OCR();


private:
    QPushButton *choixFichier;
    QPushButton *go;

    QPushButton *bouton1;
    QPushButton *bouton2;
    QPushButton *bouton3;
    QPushButton *bouton4;
    QPushButton *bouton5;
    QPushButton *bouton6;
    QPushButton *bouton7;
    QLCDNumber  *lcd;
    QLCDNumber  *lcd1;
    QLCDNumber  *lcd2;
    QLCDNumber  *lcd3;
    QLCDNumber  *lcd4;
    QLCDNumber  *lcd5;
    QLCDNumber  *lcd6;
    QSlider     *slider;
    QLabel      *texte;
    QLabel      *texte1;
    QLabel      *texte2;
    QLabel      *texte3;
    QLabel      *texte4;
    QLabel      *texte5;
    QLabel      *texte6;
    QCheckBox   *cocheseuil;
    QTabWidget  *onglets;
    QLabel      *page1;
    QLabel      *page2;
    QLabel      *page3;
    QLabel      *page5;

    QString nomFichier;
    QString marqueur;


};

#endif // FONCTION
